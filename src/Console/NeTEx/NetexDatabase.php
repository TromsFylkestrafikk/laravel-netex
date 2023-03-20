<?php

namespace TromsFylkestrafikk\Netex\Console\NeTEx;

use Illuminate\Support\Facades\DB;
use TromsFylkestrafikk\Netex\Services\DbBulkInsert;

class NetexDatabase
{
    protected $stats = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Truncate all tables from the main NeTEx file.
        $this->truncateTable('netex_calendar');
        $this->truncateTable('netex_operators');
        $this->truncateTable('netex_line_groups');
        $this->truncateTable('netex_stop_points');
        $this->truncateTable('netex_stop_assignments');
        $this->truncateTable('netex_destination_displays');
        $this->truncateTable('netex_service_links');
        $this->truncateTable('netex_vehicle_schedules');
        $this->truncateTable('netex_vehicle_blocks');

        // Truncate all tables from line NeTEx files.
        $this->truncateTable('netex_vehicle_journeys');
        $this->truncateTable('netex_passing_times');
        $this->truncateTable('netex_journey_patterns');
        $this->truncateTable('netex_journey_pattern_stop_point');
        $this->truncateTable('netex_journey_pattern_link');
        $this->truncateTable('netex_routes');
        $this->truncateTable('netex_route_point_sequence');
        $this->truncateTable('netex_lines');
    }

    /**
     * Write to database (calendar).
     *
     * @param  object[]  $data
     */
    public function writeCalendar($data)
    {
        $dumper = new DbBulkInsert('netex_calendar');
        foreach ($data as $id => $cal) {
            $dumper->addRecord([
                'id' => $id,
                'ref' => $cal['ref'],
                'date' => $cal['date']
            ]);
        }
        $this->stats['Calendar'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (operators).
     *
     * @param  object[]  $data
     */
    public function writeOperators($data)
    {
        $dumper = new DbBulkInsert('netex_operators');
        foreach ($data as $id => $operator) {
            $dumper->addRecord([
                'id' => $id,
                'name' => $operator['Name'],
                'legal_name' => $operator['LegalName'],
                'company_number' => $operator['CompanyNumber']
            ]);
        }
        $this->stats['Operators'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (groupOfLines).
     *
     * @param  object[]  $data
     */
    public function writeGroupOfLines($data)
    {
        $dumper = new DbBulkInsert('netex_line_groups');
        foreach ($data as $id => $group) {
            $dumper->addRecord([
                'id' => $id,
                'name' => $group['Name']
            ]);
        }
        $this->stats['Line groups'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (lines).
     *
     * @param  object[]  $data
     */
    public function writeLines($data)
    {
        $dumper = new DbBulkInsert('netex_lines');
        foreach ($data as $id => $line) {
            $dumper->addRecord([
                'id' => $id,
                'name' => $line['Name'],
                'transport_mode' => $line['TransportMode'],
                'transport_submode' => $line['TransportSubmode'],
                'public_code' => $line['PublicCode'],
                'private_code' => $line['PrivateCode'],
                'operator_ref' => $line['OperatorRef'],
                'line_group_ref' => $line['RepresentedByGroupRef']
            ]);
        }
        $this->stats['Lines'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (scheduledStopPoints).
     *
     * @param  object[]  $data
     */
    public function writeScheduledStopPoints($data)
    {
        $dumper = new DbBulkInsert('netex_stop_points');
        foreach ($data as $id => $sp) {
            $dumper->addRecord([
                'id' => $id,
                'name' => $sp['Name']
            ]);
        }
        $this->stats['Stop points'] = $dumper->flush()->getRecordsWritten();
    }

    public function writeDestinationDisplays($displays)
    {
        $dumper = new DbBulkInsert('netex_destination_displays');
        foreach ($displays as $id => $display) {
            $dumper->addRecord([
                'id' => $id,
                'front_text' => $display['FrontText']
            ]);
        }
        $this->stats['Destination displays'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (serviceLinks).
     *
     * @param  object[]  $data
     */
    public function writeServiceLinks($data)
    {
        $dumper = new DbBulkInsert('netex_service_links');
        foreach ($data as $id => $sl) {
            $dumper->addRecord([
                'id' => $id,
                'distance' => $sl['Distance'],
                'srs_dimension' => $sl['srsDimension'],
                'count' => $sl['count'],
                'pos_list' => $sl['posList']
            ]);
        }
        $this->stats['Service links'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (stopAssignments).
     *
     * @param  object[]  $data
     */
    public function writeStopAssignments($data)
    {
        $dumper = new DbBulkInsert('netex_stop_assignments');
        foreach ($data as $id => $sa) {
            $dumper->addRecord([
                'id' => $id,
                'order' => $sa['order'],
                'stop_point_ref' => $sa['ScheduledStopPointRef'],
                'quay_ref' => $sa['QuayRef']
            ]);
        }
        $this->stats['Stop assignments'] = $dumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (vehicleSchedules).
     *
     * @param  object[]  $data
     */
    public function writeVehicleSchedules($data)
    {
        $blockDump = new DbBulkInsert('netex_vehicle_blocks');
        $vsDump = new DbBulkInsert('netex_vehicle_schedules');
        foreach ($data as $vs) {
            $blockDump->addRecord([
                'id' => $vs['BlockRef'],
                'private_code' => $vs['PrivateCode'],
                'calendar_ref' => $vs['DayTypeRef'],
            ]);
            foreach ($vs['journeys'] as $journey) {
                $vsDump->addRecord([
                    'vehicle_block_ref' => $vs['BlockRef'],
                    'vehicle_journey_ref' => $journey['VehicleJourneyRef']
                ]);
            }
        }
        $vsDump->flush();
        $this->stats['Vehicle blocks'] = $blockDump->flush()->getRecordsWritten();
    }

    /**
     * Write to database (vehicleJourneys).
     *
     * @param  object[]  $data
     */
    public function writeVehicleJourneys($data)
    {
        $vjDumper = new DbBulkInsert('netex_vehicle_journeys');
        $ptDumper = new DbBulkInsert('netex_passing_times');

        foreach ($data as $id => $vj) {
            $vjDumper->addRecord([
                'id' => $id,
                'name' => $vj['Name'],
                'private_code' => $vj['PrivateCode'],
                'journey_pattern_ref' => $vj['JourneyPatternRef'],
                'operator_ref' => $vj['OperatorRef'],
                'line_ref' => $vj['LineRef'],
                'calendar_ref' => $vj['DayTypeRef']
            ]);
            foreach ($vj['passingTimes'] as $ptID => $pt) {
                $ptDumper->addRecord([
                    'vehicle_journey_ref' => $id,
                    'arrival_time' => $pt['ArrivalTime'],
                    'departure_time' => $pt['DepartureTime'],
                    'journey_pattern_stop_point_ref' => $pt['StopPointInJourneyPatternRef']
                ]);
            }
        }
        $ptDumper->flush();
        $this->stats['Vehicle journeys'] = $vjDumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (journeyPatterns).
     *
     * @param  object[]  $data
     */
    public function writeJourneyPatterns($data)
    {
        $jpDumper = new DbBulkInsert('netex_journey_patterns');
        $pspDumper = new DbBulkInsert('netex_journey_pattern_stop_point');
        $jplDumper = new DbBulkInsert('netex_journey_pattern_link');
        foreach ($data as $id => $jp) {
            $jpDumper->addRecord([
                'id' => $id,
                'name' => $jp['Name'],
                'route_ref' => $jp['RouteRef']
            ]);
            foreach ($jp['pointsInSequence'] as $point) {
                $pspDumper->addRecord([
                    'id' => $point['id'],
                    'journey_pattern_ref' => $id,
                    'order' => $point['order'],
                    'stop_point_ref' => $point['ScheduledStopPointRef'],
                    'alighting' => $point['ForAlighting'],
                    'boarding' => $point['ForBoarding'],
                    'destination_display_ref' => $point['DestinationDisplayRef'],
                ]);
            }
            foreach ($jp['linksInSequence'] as $link) {
                $jplDumper->addRecord([
                    'id' => $link['id'],
                    'journey_pattern_ref' => $id,
                    'order' => $link['order'],
                    'service_link_ref' => $link['ServiceLinkRef']
                ]);
            }
        }
        $pspDumper->flush();
        $jplDumper->flush();
        $this->stats['Journey patterns'] = $jpDumper->flush()->getRecordsWritten();
    }

    /**
     * Write to database (routes).
     *
     * @param  object[]  $data
     */
    public function writeRoutes($data)
    {
        $routeDumper = new DbBulkInsert('netex_routes');
        $seqDumper = new DbBulkInsert('netex_route_point_sequence');
        foreach ($data as $id => $route) {
            $routeDumper->addRecord([
                'id' => $id,
                'name' => $route['Name'],
                'short_name' => $route['ShortName'],
                'line_ref' => $route['LineRef'],
                'direction' => $route['DirectionType']
            ]);
            foreach ($route['pointsInSequence'] as $point) {
                $seqDumper->addRecord([
                    'route_ref' => $id,
                    'order' => $point['order'],
                    'stop_point_ref' => $point['RoutePointRef'],
                ]);
            }
        }
        $seqDumper->flush();
        $this->stats['Routes'] = $routeDumper->flush()->getRecordsWritten();
    }

    /**
     * Statistics of written entries.
     *
     * @return int[]
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * String representation of written records.
     */
    public function statsToStr(): string
    {
        return implode(', ', array_map(
            fn ($key) => "$key: {$this->stats[$key]}",
            array_keys($this->stats)
        ));
    }

    /**
     * Reset db statistics.
     */
    public function resetStats(): self
    {
        $this->stats = [];
        return $this;
    }

    /**
     * Truncate database table.
     *
     * @param  string  $name
     *   The table's name
     */
    protected function truncateTable($name)
    {
        DB::table($name)->truncate();
    }
}
