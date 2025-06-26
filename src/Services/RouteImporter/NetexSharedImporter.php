<?php

namespace TromsFylkestrafikk\Netex\Services\RouteImporter;

use Illuminate\Support\Carbon;
use SimpleXMLElement;

/**
 * Import a NeTEx shared data file.
 */
class NetexSharedImporter extends NetexImporterBase
{
    /**
     * List of data frames to act upon in NeTEx data.
     *
     * The key is used
     */
    protected $frames = [
        'Operator' => [
            'path' => ['ResourceFrame', 'organisations', 'Operator'],
            'table' => 'netex_operators'
        ],
        'GroupOfLines' => [
            'path' => ['ServiceFrame', 'Network', 'groupsOfLines', 'GroupOfLines'],
            'table' => 'netex_line_groups',
        ],
        'DestinationDisplay' => [
            'path' => ['ServiceFrame', 'destinationDisplays', 'DestinationDisplay'],
            'table' => 'netex_destination_displays',
        ],
        'ScheduledStopPoint' => [
            'path' => ['ServiceFrame', 'scheduledStopPoints', 'ScheduledStopPoint'],
            'table' => 'netex_stop_points',
        ],
        'ServiceLink' => [
            'path' => ['ServiceFrame', 'serviceLinks', 'ServiceLink'],
            'table' => 'netex_service_links',
        ],
        'PassengerStopAssignment' => [
            'path' => ['ServiceFrame', 'stopAssignments', 'PassengerStopAssignment'],
            'table' => 'netex_stop_assignments',
        ],
        'Notice' => [
            'path' => ['ServiceFrame', 'notices', 'Notice'],
            'table' => 'netex_notices',
        ],
        'Block' => [
            'path' => ['VehicleScheduleFrame', 'blocks', 'Block'],
            'table' => 'netex_vehicle_blocks',
        ],
        'BlockJourneyRef'   => ['table' => 'netex_vehicle_schedules'],
        'Calendar'          => ['table' => 'netex_calendar'],
        'DayType'           => ['path' => ['ServiceCalendarFrame', 'dayTypes', 'DayType']],
        'OperatingDay'      => [
            'path' => ['ServiceCalendarFrame', 'operatingDays', 'OperatingDay'],
            'table' => 'netex_operating_days',
        ],
        'OperatingPeriod'   => ['path' => ['ServiceCalendarFrame', 'operatingPeriods', 'OperatingPeriod']],
        'DayTypeAssignment' => ['path' => ['ServiceCalendarFrame', 'dayTypeAssignments', 'DayTypeAssignment']],

    ];

    /**
     * Various storage containers during parsing of calendar dates.
     */
    protected $dayTypes = [];
    protected $operatingPeriods = [];
    protected $dayTypeAssignments = [];

    public function import(): NetexSharedImporter
    {
        parent::import();
        $this->writeCalendar();
        return $this;
    }

    protected function readOperator(SimpleXMLElement $xml): array
    {
        // Hack, but set the version of this route set to the version attribute
        // found here.
        $this->version = (string) $xml['version'];
        return [
            'id' => $xml['id'],
            'company_number' => $xml->CompanyNumber ?? null,
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

    protected function readOperatingDay(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'calendar_date' => $xml->CalendarDate,
        ];
    }

    protected function readDayType(SimpleXMLElement $xml): array|null
    {
        $id = (string) $xml['id'];
        $this->dayTypes[$id]['DaysOfWeek'] = isset($xml->properties)
           ? ((string) $xml->properties->PropertyOfDay->DaysOfWeek)
           : null;
        return null;
    }

    protected function readOperatingPeriod(SimpleXMLElement $xml): array|null
    {
        $id = (string) $xml['id'];
        $this->operatingPeriods[$id]['FromDate'] = $xml->FromDate;
        $this->operatingPeriods[$id]['ToDate'] = $xml->ToDate;
        return null;
    }

    protected function readDayTypeAssignment(SimpleXMLElement $xml): array|null
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
                if (!$isAvail) {
                    continue;
                }
                $this->dumpers['Calendar']->addRecord([
                    'id' => "$date-$dayTypeRef",
                    'date' => $date,
                    'ref' => $dayTypeRef,
                ]);
            }
        }
        $this->dumpers['Calendar']->flush();
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
