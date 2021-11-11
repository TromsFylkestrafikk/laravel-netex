<?php

namespace TromsFylkestrafikk\Netex\Console\NeTEx;

use Illuminate\Support\Facades\Log;
use DB;
use Exception;

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
        $this->truncateTable('netex_service_links');
        $this->truncateTable('netex_vehicle_schedules');

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

        foreach ($data as $id => $cal) {
            DB::table('netex_calendar')->insert([
                'id' => $id,
                'ref' => $cal['ref'],
                'date' => $cal['date']
            ]);
        }

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

        foreach ($data as $id => $operator) {
            DB::table('netex_operators')->insert([
                'id' => $id,
                'name' => $operator['Name'],
                'legal_name' => $operator['LegalName'],
                'company_number' => $operator['CompanyNumber']
            ]);
        }

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

        foreach ($data as $id => $group) {
            DB::table('netex_line_groups')->insert([
                'id' => $id,
                'name' => $group['Name']
            ]);
        }

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

        foreach ($data as $id => $line) {
            DB::table('netex_lines')->insert([
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

        foreach ($data as $id => $sp) {
            DB::table('netex_stop_points')->insert([
                'id' => $id,
                'name' => $sp['Name']
            ]);
        }

        Log::info('New stop points added to database: ' . count($data));
    }

    /**
     * Write to database (serviceLinks).
     *
     * @param  object[]  $data
     */
    public function writeServiceLinks($data)
    {
        Log::debug('Writing to database: Service links');

        foreach ($data as $id => $sl) {
            DB::table('netex_service_links')->insert([
                'id' => $id,
                'distance' => $sl['Distance'],
                'srs_dimension' => $sl['srsDimension'],
                'count' => $sl['count'],
                'pos_list' => $sl['posList']
            ]);
        }

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

        foreach ($data as $id => $sa) {
            DB::table('netex_stop_assignments')->insert([
                'id' => $id,
                'order' => $sa['order'],
                'stop_point_ref' => $sa['ScheduledStopPointRef'],
                'quay_ref' => $sa['QuayRef']
            ]);
        }

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

        foreach ($data as $id => $vs) {
            foreach ($vs['journeys'] as $journeyID => $journey) {
                DB::table('netex_vehicle_schedules')->insert([
                    'calendar_ref' => $vs['DayTypeRef'],
                    'vehicle_journey_ref' => $journey['VehicleJourneyRef']
                ]);
            }
        }

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

        foreach ($data as $id => $vj) {
            DB::table('netex_vehicle_journeys')->insert([
                'id' => $id,
                'name' => $vj['Name'],
                'private_code' => $vj['PrivateCode'],
                'journey_pattern_ref' => $vj['JourneyPatternRef'],
                'operator_ref' => $vj['OperatorRef'],
                'line_ref' => $vj['LineRef'],
                'calendar_ref' => $vj['DayTypeRef']
            ]);
            foreach ($vj['passingTimes'] as $ptID => $pt) {
                DB::table('netex_passing_times')->insert([
                    'vehicle_journey_ref' => $id,
                    'arrival_time' => $pt['ArrivalTime'],
                    'departure_time' => $pt['DepartureTime'],
                    'journey_pattern_stop_point_ref' => $pt['StopPointInJourneyPatternRef']
                ]);
            }
        }

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

        foreach ($data as $id => $jp) {
            DB::table('netex_journey_patterns')->insert([
                'id' => $id,
                'name' => $jp['Name'],
                'route_ref' => $jp['RouteRef']
            ]);
            foreach ($jp['pointsInSequence'] as $point) {
                DB::table('netex_journey_pattern_stop_point')->insert([
                    'id' => $point['id'],
                    'journey_pattern_ref' => $id,
                    'order' => $point['order'],
                    'stop_point_ref' => $point['ScheduledStopPointRef'],
                    'alighting' => $point['ForAlighting'],
                    'boarding' => $point['ForBoarding']
                ]);
            }
            foreach ($jp['linksInSequence'] as $link) {
                DB::table('netex_journey_pattern_link')->insert([
                    'id' => $link['id'],
                    'journey_pattern_ref' => $id,
                    'order' => $link['order'],
                    'service_link_ref' => $link['ServiceLinkRef']
                ]);
            }
        }

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

        foreach ($data as $id => $route) {
            DB::table('netex_routes')->insert([
                'id' => $id,
                'name' => $route['Name'],
                'short_name' => $route['ShortName'],
                'line_ref' => $route['LineRef'],
                'direction' => $route['DirectionType']
            ]);
            foreach ($route['pointsInSequence'] as $pointID => $point) {
                DB::table('netex_route_point_sequence')->insert([
                    'route_ref' => $id,
                    'order' => $point['order'],
                    'stop_point_ref' => $point['RoutePointRef']
                ]);
            }
        }

        Log::info('New routes added to database: ' . count($data));
    }
}
