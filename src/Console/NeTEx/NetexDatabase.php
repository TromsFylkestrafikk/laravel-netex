<?php

namespace TromsFylkestrafikk\Netex\Console\NeTEx;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use TromsFylkestrafikk\Netex\Services\DbBulkInsert;

class NetexDatabase
{
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

        Log::debug('All NeTEx route data tables truncated.');
    }

    /**
     * Truncate database table.
     *
     * @param  string  $name
     *   The table's name
     */
    protected function truncateTable($name)
    {
        try {
            DB::table($name)->truncate();
            Log::debug('Table truncated: ' . $name);
        } catch (Exception $e) {
            Log::error('Failed to truncate table (' . $name . ')! ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Write to database (calendar).
     *
     * @param  object[]  $data
     */
    public function writeCalendar($data)
    {
        Log::debug('Writing to database: Calendar');
        $dumper = new DbBulkInsert('netex_calendar');
        foreach ($data as $id => $cal) {
            $dumper->addRecord([
                'id' => $id,
                'ref' => $cal['ref'],
                'date' => $cal['date']
            ]);
        }
        $dumper->flush();

        Log::info('New calendar entries added to database: ' . count($data));
    }

    /**
     * Write to database (operators).
     *
     * @param  object[]  $data
     */
    public function writeOperators($data)
    {
        Log::debug('Writing to database: Operators');

        $dumper = new DbBulkInsert('netex_operators');
        foreach ($data as $id => $operator) {
            $dumper->addRecord([
                'id' => $id,
                'name' => $operator['Name'],
                'legal_name' => $operator['LegalName'],
                'company_number' => $operator['CompanyNumber']
            ]);
        }
        $dumper->flush();

        Log::info('New operators added to database: ' . count($data));
    }

    /**
     * Write to database (groupOfLines).
     *
     * @param  object[]  $data
     */
    public function writeGroupOfLines($data)
    {
        Log::debug('Writing to database: Line groups');
        $dumper = new DbBulkInsert('netex_line_groups');
        foreach ($data as $id => $group) {
            $dumper->addRecord([
                'id' => $id,
                'name' => $group['Name']
            ]);
        }
        $dumper->flush();

        Log::info('New line groups added to database: ' . count($data));
    }

    /**
     * Write to database (lines).
     *
     * @param  object[]  $data
     */
    public function writeLines($data)
    {
        Log::debug('Writing to database: Lines');

        $writer = new DbBulkInsert('netex_lines');
        foreach ($data as $id => $line) {
            $writer->addRecord([
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
        $writer->flush();

        Log::info('New lines added to database: ' . count($data));
    }

    /**
     * Write to database (scheduledStopPoints).
     *
     * @param  object[]  $data
     */
    public function writeScheduledStopPoints($data)
    {
        Log::debug('Writing to database: Stop points');

        $writer = new DbBulkInsert('netex_stop_points');
        foreach ($data as $id => $sp) {
            $writer->addRecord([
                'id' => $id,
                'name' => $sp['Name']
            ]);
        }
        $writer->flush();

        Log::info('New stop points added to database: ' . count($data));
    }

    public function writeDestinationDisplays($displays)
    {
        Log::debug('Writing to database: Destination displays');
        $dumper = new DbBulkInsert('netex_destination_displays');
        foreach ($displays as $id => $display) {
            $dumper->addRecord([
                'id' => $id,
                'front_text' => $display['FrontText']
            ]);
        }
        $dumper->flush();
        Log::info('New destination displays added to database: ' . count($displays));
    }

    /**
     * Write to database (serviceLinks).
     *
     * @param  object[]  $data
     */
    public function writeServiceLinks($data)
    {
        Log::debug('Writing to database: Service links');
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
        $dumper->flush();
        Log::info('New service links added to database: ' . count($data));
    }

    /**
     * Write to database (stopAssignments).
     *
     * @param  object[]  $data
     */
    public function writeStopAssignments($data)
    {
        Log::debug('Writing to database: Stop assignments');
        $dumper = new DbBulkInsert('netex_stop_assignments');

        foreach ($data as $id => $sa) {
            $dumper->addRecord([
                'id' => $id,
                'order' => $sa['order'],
                'stop_point_ref' => $sa['ScheduledStopPointRef'],
                'quay_ref' => $sa['QuayRef']
            ]);
        }
        $dumper->flush();

        Log::info('New stop assignments added to database: ' . count($data));
    }

    /**
     * Write to database (vehicleSchedules).
     *
     * @param  object[]  $data
     */
    public function writeVehicleSchedules($data)
    {
        Log::debug('Writing to database: Vehicle schedules');

        $vsDump = new DbBulkInsert('netex_vehicle_schedules');
        $blockDump = new DbBulkInsert('netex_vehicle_blocks');
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
        $blockDump->flush();

        Log::info('New vehicle schedules added to database: ' . count($data));
    }

    /**
     * Write to database (vehicleJourneys).
     *
     * @param  object[]  $data
     */
    public function writeVehicleJourneys($data)
    {
        Log::debug('Writing to database: Vehicle journeys');

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
        $vjDumper->flush();
        $ptDumper->flush();

        Log::info('New vehicle journeys added to database: ' . count($data));
    }

    /**
     * Write to database (journeyPatterns).
     *
     * @param  object[]  $data
     */
    public function writeJourneyPatterns($data)
    {
        Log::debug('Writing to database: Journey patterns');
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
        $jpDumper->flush();
        $pspDumper->flush();
        $jplDumper->flush();

        Log::info('New journey patterns added to database: ' . count($data));
    }

    /**
     * Write to database (routes).
     *
     * @param  object[]  $data
     */
    public function writeRoutes($data)
    {
        Log::debug('Writing to database: Routes');
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
        $routeDumper->flush();
        $seqDumper->flush();

        Log::info('New routes added to database: ' . count($data));
    }
}
