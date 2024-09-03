<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;

/**
 * Heavy lifting tool for stuffing NeTEx data into sql tables.
 */
class RouteImporter
{
    public $description = null;
    public $availableFrom = null;
    public $availableTo = null;
    public $version = null;

    /**
     * @var DbBulkInsert[]
     */
    protected $dumpers = [];

    /**
     * Various storage containers during parsing of calendar dates.
     */
    protected $dayTypes = [];
    protected $operatingPeriods = [];
    protected $dayTypeAssignments = [];

    public function __construct(protected RouteSet $set)
    {
        //
    }

    /**
     * Import full set of NeTEx data.
     */
    public function importSet(): RouteImporter
    {
        foreach ($this->set->getSharedFiles() as $sharedFile) {
            $this->importSharedData($sharedFile);
        }

        foreach ($this->set->getLineFiles() as $lineFile) {
            $this->importLineData($lineFile);
        }
        return $this;
    }

    /**
     * Import this XML file as shared NeTEx route data.
     *
     * @param string $xmlFile Full path of XML file
     */
    public function importSharedData(string $xmlFile): RouteImporter
    {
        $this->dayTypes = [];
        $this->operatingPeriods = [];
        $this->dayTypeAssignments = [];

        $elementToTable = [
            'Operator' => 'netex_operators',
            'GroupOfLines' => 'netex_line_groups',
            'DestinationDisplay' => 'netex_destination_displays',
            'ScheduledStopPoint' => 'netex_stop_points',
            'ServiceLink' => 'netex_service_links',
            'PassengerStopAssignment' => 'netex_stop_assignments',
            'Notice' => 'netex_notices',
            'Block' => 'netex_vehicle_blocks',
            'BlockJourneyRef' => 'netex_vehicle_schedules',
            'Calendar' => 'netex_calendar',
        ];
        foreach ($elementToTable as $element => $table) {
            DB::table($table)->truncate();
            $this->dumpers[$element] = new DbBulkInsert($table);
        }
        $reader = new ChristmasTreeParser();
        $reader->open($xmlFile);
        $reader->addCallback(
            ['PublicationDelivery', 'dataObjects', 'CompositeFrame', 'validityConditions', 'AvailabilityCondition'],
            function (ChristmasTreeParser $reader) {
                $xml = $reader->expandSimpleXml();
                $this->availableFrom = $xml->FromDate;
                $this->availableTo = $xml->ToDate;
            }
        )->withParents(
            ['PublicationDelivery', 'dataObjects', 'CompositeFrame', 'frames'],
            fn ($reader) => $this->mapFramesReaders($reader)
        )->parse();

        foreach ($this->dumpers as $dumper) {
            /** @var DbBulkInsert $dumper */
            $dumper->flush();
        }
        $this->writeCalendar();
        return $this;
    }

    public function importLineData(string $xmlFile): RouteImporter
    {
        return $this;
    }

    protected function mapFramesReaders(ChristmasTreeParser $reader): void
    {
        // List of element paths to read. The last element must have a
        // corresponding reader method. So if the last element is
        // 'Operator', there must be a method named 'readOperator())'
        $framesChildren = [
            ['ResourceFrame', 'organisations', 'Operator'],
            ['ServiceFrame', 'Network', 'groupsOfLines', 'GroupOfLines'],
            ['ServiceFrame', 'destinationDisplays', 'DestinationDisplay'],
            ['ServiceFrame', 'scheduledStopPoints', 'ScheduledStopPoint'],
            ['ServiceFrame', 'serviceLinks', 'ServiceLink'],
            ['ServiceFrame', 'stopAssignments', 'PassengerStopAssignment'],
            ['ServiceFrame', 'notices', 'Notice'],
            ['VehicleScheduleFrame', 'blocks', 'Block'],
            ['ServiceCalendarFrame', 'dayTypes', 'DayType'],
            ['ServiceCalendarFrame', 'operatingPeriods', 'OperatingPeriod'],
            ['ServiceCalendarFrame', 'dayTypeAssignments', 'DayTypeAssignment'],
        ];
        foreach ($framesChildren as $children) {
            $leafElement = end($children);
            if (!method_exists($this, "read$leafElement")) {
                continue;
            }
            $reader->addCallback($children, function (ChristmasTreeParser $reader) use ($leafElement) {
                $xml = $reader->expandSimpleXml();
                $record = $this->{"read$leafElement"}($xml);
                if (! $record) {
                    return;
                }
                $this->dumpers[$leafElement]->addRecord($record);
            });
        }
    }

    protected function readOperator(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'company_number' => $xml->CompanyNumber,
            'legal_name' => $xml->LegalName,
            'name' => $xml->Name,
        ];
    }

    protected function readGroupOfLines(SimpleXMLElement $xml): array
    {
        return ['id' => $xml['id'], 'name' => $xml->Name];
    }

    protected function readDestinationDisplay(SimpleXMLElement $xml): array
    {
        return ['id' => $xml['id'], 'front_text' => $xml->FrontText];
    }

    protected function readScheduledStopPoint(SimpleXMLElement $xml): array
    {
        return ['id' => $xml['id'], 'name' => $xml->Name];
    }

    protected function readServiceLink(SimpleXMLElement $xml): array|null
    {
        if (!isset($xml->projections->LinkSequenceProjection)) {
            return null;
        }
        $proj = $xml->projections->LinkSequenceProjection->children('http://www.opengis.net/gml/3.2');
        if ($proj->count() < 1) {
            return null;
        }
        return [
            'id' => $xml['id'],
            'distance' => $xml->Distance,
            'srs_dimension' => $proj->LineString->posList->attributes()['srsDimension'],
            'count' => $proj->LineString->posList->attributes()['count'],
            'pos_list' => $proj->LineString->posList,
        ];
    }

    protected function readPassengerStopAssignment(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'order' => $xml['order'],
            'stop_point_ref' => $xml->ScheduledStopPointRef['ref'],
            'quay_ref' => $xml->QuayRef['ref'],
        ];
    }

    protected function readNotice(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'text' => $xml->Text,
            'public_code' => $xml->PublicCode,
        ];
    }

    protected function readBlock(SimpleXMLElement $xml): array
    {
        $blockId = $xml['id'];
        foreach ($xml->journeys->VehicleJourneyRef as $journeyRef) {
            $this->dumpers['BlockJourneyRef']->addRecord([
                'vehicle_block_ref' => $blockId,
                'vehicle_journey_ref' => $journeyRef['ref'],
            ]);
        }
        return [
            'id' => $blockId,
            'private_code' => $xml->PrivateCode,
            'calendar_ref' => $xml->dayTypes->DayTypeRef['ref'],
        ];
    }

    protected function readDayType(SimpleXMLElement $xml): null
    {
        $id = (string) $xml['id'];
        $this->dayTypes[$id]['DaysOfWeek'] = isset($xml->properties)
           ? ((string) $xml->properties->PropertyOfDay->DaysOfWeek)
           : null;
        return null;
    }

    protected function readOperatingPeriod(SimpleXMLElement $xml): null
    {
        $id = (string) $xml['id'];
        $this->operatingPeriods[$id]['FromDate'] = $xml->FromDate;
        $this->operatingPeriods[$id]['ToDate'] = $xml->ToDate;
        return null;
    }

    protected function readDayTypeAssignment(SimpleXMLElement $xml): null
    {
        $id = (string) $xml['id'];
        $this->dayTypeAssignments[$id]['order'] = (int) $xml['order'];
        $this->dayTypeAssignments[$id]['isAvailable'] = ((string) $xml->isAvailable) !== 'false';
        $this->dayTypeAssignments[$id]['OperatingPeriodRef'] = isset($xml->OperatingPeriodRef)
            ? (string) $xml->OperatingPeriodRef['ref']
            : null;
        $this->dayTypeAssignments[$id]['DayTypeRef'] = (string) $xml->DayTypeRef['ref'];
        $this->dayTypeAssignments[$id]['Date'] = (string) $xml->Date;
        return null;
    }

    /**
     * Write to DB an unrolled version of buffered calendar state.
     */
    protected function writeCalendar(): void
    {
        $unrolled = [];
        // Step 1: Re-structure daytype and dates into a two-dimmensional array
        // with date and daytype as dimmensions and availability as value.
        foreach ($this->dayTypeAssignments as $dayTypeId => $assignment) {
            $dayTypeRef = $assignment['DayTypeRef'];
            if (!empty($assignment['Date'])) {
                $unrolled[$assignment['Date']][$dayTypeRef] = $assignment['isAvailable'];
                continue;
            }
            if (empty($assignment['OperatingPeriodRef'])) {
                continue;
            }
            foreach (
                $this->unrollDates(
                    $this->operatingPeriods[$assignment['OperatingPeriodRef']],
                    $this->dayTypes[$dayTypeRef]
                ) as $date
            ) {
                $unrolled[$date][$dayTypeRef] = $assignment['isAvailable'];
            }
        }

        // Step 2: Dump available dates/daytypes to calendar table.
        foreach ($unrolled as $date => $dayTypes) {
            foreach ($dayTypes as $dayTypeRef => $isAvail) {
                if ($isAvail) {
                    $this->dumpers['Calendar']->addRecord([
                        'id' => "$date-$dayTypeRef",
                        'date' => $date,
                        'ref' => $dayTypeRef,
                    ]);
                }
            }
        }
    }

    /**
     * Given a period and daytype, unroll all dates within it.
     *
     * @return string[] List of ISO dates.
     */
    protected function unrollDates($period, $dayType): array
    {
        $dates = [];
        $fromDate = new Carbon($period['FromDate']);
        $toDate = new Carbon($period['ToDate']);
        $daysOfWeek = $this->daysToDow($dayType['DaysOfWeek']);
        Log::debug(sprintf("%s => %s", $dayType['DaysOfWeek'], implode(', ', $daysOfWeek)));
        while ($fromDate->lessThanOrEqualTo($toDate)) {
            if (!empty($daysOfWeek[$fromDate->dayOfWeekIso])) {
                $dates[] = $fromDate->toDateString();
            }
            $fromDate->addDay();
        }
        return $dates;
    }

    /**
     * Convert NeTEx DaysOfWeek strings into a iso DOW array
     *
     * @see https://enturas.atlassian.net/wiki/spaces/PUBLIC/pages/728727624/framework#PropertyOfDay
     *
     * @param string $days
     *
     * @return string[] Unrolled list of all days, keyed by ISO day number
     */
    protected function daysToDow(string $days): array
    {
        if (strlen($days) < 5) {
            return [];
        }
        $days = str_replace('Everyday', 'Weekdays Weekend', $days);
        $days = str_replace('Weekdays', 'Monday Tuesday Wednesday Thursday Friday', $days);
        $days = str_replace('Weekend', 'Saturday Sunday', $days);
        return array_intersect([
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ], explode(' ', $days));
    }
}
