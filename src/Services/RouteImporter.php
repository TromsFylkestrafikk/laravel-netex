<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;
use TromsFylkestrafikk\Netex\Services\RouteImporter\SharedFileImporter;
use TromsFylkestrafikk\Netex\Services\RouteImporter\LineFileImporter;

/**
 * Heavy lifting tool for stuffing NeTEx data into sql tables.
 */
class RouteImporter
{
    public function __construct(protected RouteSet $set)
    {
        //
    }

    /**
     * Import full set of NeTEx data.
     */
    public function importSet(): RouteImporter
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
        $this->truncateTable('netex_notices');

        // Truncate all tables from line NeTEx files.
        $this->truncateTable('netex_vehicle_journeys');
        $this->truncateTable('netex_passing_times');
        $this->truncateTable('netex_journey_patterns');
        $this->truncateTable('netex_journey_pattern_stop_point');
        $this->truncateTable('netex_journey_pattern_link');
        $this->truncateTable('netex_routes');
        $this->truncateTable('netex_route_point_sequence');
        $this->truncateTable('netex_lines');
        $this->truncateTable('netex_notice_assignments');

        foreach ($this->set->getSharedFiles() as $sharedFile) {
            Log::debug(sprintf('Writing shared file %s', $sharedFile));
            SharedFileImporter::importFile($sharedFile);
        }

        foreach ($this->set->getLineFiles() as $lineFile) {
            Log::debug(sprintf('Writing line file %s', $lineFile));
            LineFileImporter::importFile($lineFile);
        }
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
