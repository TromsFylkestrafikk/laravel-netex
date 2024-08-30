<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;

/**
 * Heavy lifting tools for stuffing NeTEx data into sql tables.
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
            function (ChristmasTreeParser $reader) {
                // List of element paths to read. The last element must have a
                // corresponding reader method. So if the last element is
                // 'Operator', there must be a method named 'readOperator())'
                $underFrames = [
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
                foreach ($underFrames as $children) {
                    $leafElement = end($children);
                    if (method_exists($this, "read$leafElement")) {
                        $reader->addCallback($children, function (ChristmasTreeParser $reader) use ($leafElement) {
                            $xml = $reader->expandSimpleXml();
                            $this->{"read$leafElement"}($reader, $xml);
                        });
                    }
                }
            }
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

    protected function readOperator(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['Operator']->addRecord([
            'id' => $xml['id'],
            'company_number' => $xml->CompanyNumber,
            'legal_name' => $xml->LegalName,
            'name' => $xml->Name,
        ]);
    }

    protected function readGroupOfLines(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['GroupOfLines']->addRecord([
            'id' => $xml['id'],
            'name' => $xml->Name,
        ]);
    }

    protected function readDestinationDisplay(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['DestinationDisplay']->addRecord([
            'id' => $xml['id'],
            'front_text' => $xml->FrontText,
        ]);
    }

    protected function readScheduledStopPoint(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['ScheduledStopPoint']->addRecord([
            'id' => $xml['id'],
            'name' => $xml->Name,
        ]);
    }

    protected function readServiceLink(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        if (!isset($xml->projections->LinkSequenceProjection)) {
            return;
        }
        $proj = $xml->projections->LinkSequenceProjection->children('http://www.opengis.net/gml/3.2');
        if ($proj->count() < 1) {
            return;
        }
        $this->dumpers['ServiceLink']->addRecord([
            'id' => $xml['id'],
            'distance' => $xml->Distance,
            'srs_dimension' => $proj->LineString->posList->attributes()['srsDimension'],
            'count' => $proj->LineString->posList->attributes()['count'],
            'pos_list' => $proj->LineString->posList,
        ]);
    }

    protected function readPassengerStopAssignment(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['PassengerStopAssignment']->addRecord([
            'id' => $xml['id'],
            'order' => $xml['order'],
            'stop_point_ref' => $xml->ScheduledStopPointRef['ref'],
            'quay_ref' => $xml->QuayRef['ref'],
        ]);
    }

    protected function readNotice(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['Notice']->addRecord([
            'id' => $xml['id'],
            'text' => $xml->Text,
            'public_code' => $xml->PublicCode,
        ]);
    }

    protected function readBlock(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $blockId = $xml['id'];
        $this->dumpers['Block']->addRecord([
            'id' => $blockId,
            'private_code' => $xml->PrivateCode,
            'calendar_ref' => $xml->dayTypes->DayTypeRef['ref'],
        ]);
        foreach ($xml->journeys->VehicleJourneyRef as $journeyRef) {
            $this->dumpers['BlockJourneyRef']->addRecord([
                'vehicle_block_ref' => $blockId,
                'vehicle_journey_ref' => $journeyRef['ref'],
            ]);
        }
    }

    protected function readDayType(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $id = (string) $xml['id'];
        $this->dayTypes[$id]['DaysOfWeek'] = isset($xml->properties)
           ? ((string) $xml->properties->PropertyOfDay->DaysOfWeek)
           : null;
    }

    protected function readOperatingPeriod(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $id = (string) $xml['id'];
        $this->operatingPeriods[$id]['FromDate'] = $xml->FromDate;
        $this->operatingPeriods[$id]['ToDate'] = $xml->ToDate;
    }

    protected function readDayTypeAssignment(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $id = (string) $xml['id'];
        $this->dayTypeAssignments[$id]['order'] = (int) $xml['order'];
        $this->dayTypeAssignments[$id]['isAvailable'] = ((string) $xml->isAvailable) !== 'false';
        $this->dayTypeAssignments[$id]['OperatingPeriodRef'] = isset($xml->OperatingPeriodRef)
            ? (string) $xml->OperatingPeriodRef['ref']
            : null;
        $this->dayTypeAssignments[$id]['DayTypeRef'] = (string) $xml->DayTypeRef['ref'];
        $this->dayTypeAssignments[$id]['Date'] = (string) $xml->Date;
    }

    protected function read(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['']->addRecord([
            'id' => $xml['id'],
        ]);
    }

    protected function writeCalendar(): void
    {
        $unrolled = [];
        foreach ($this->dayTypeAssignments as $dayTypeId => $assignment) {
            $dayTypeRef = $assignment['DayTypeRef'];
            if (!empty($assignment['Date'])) {
                $unrolled[$assignment['Date']][$dayTypeRef] = $assignment['isAvailable'];
                continue;
            }
            if (!empty($assignment['OperatingPeriodRef'])) {
                foreach (
                    $this->unrollDates(
                        $this->operatingPeriods[$assignment['OperatingPeriodRef']],
                        $this->dayTypes[$dayTypeRef]
                    ) as $date
                ) {
                        $unrolled[$date][$dayTypeRef] = $assignment['isAvailable'];
                }
            }
        }
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
