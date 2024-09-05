<?php

namespace TromsFylkestrafikk\Netex\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\RouteImporter\NetexImporterBase;
use TromsFylkestrafikk\Netex\Services\RouteImporter\NetexLineImporter;
use TromsFylkestrafikk\Netex\Services\RouteImporter\NetexSharedImporter;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;

/**
 * Heavy lifting tool for stuffing NeTEx data into sql tables.
 */
class RouteImporter
{
    /**
     * List of tables used during import
     */
    public static $tables = [
        // Shared data tables
        'netex_calendar',
        'netex_operators',
        'netex_line_groups',
        'netex_stop_points',
        'netex_stop_assignments',
        'netex_destination_displays',
        'netex_service_links',
        'netex_vehicle_schedules',
        'netex_vehicle_blocks',
        'netex_notices',

        // Line data tables
        'netex_vehicle_journeys',
        'netex_passing_times',
        'netex_journey_patterns',
        'netex_journey_pattern_stop_point',
        'netex_journey_pattern_link',
        'netex_routes',
        'netex_route_point_sequence',
        'netex_lines',
        'netex_notice_assignments',
    ];

    /**
     * @var Carbon|null
     */
    public $availableFrom = null;

    /**
     * @var Carbon|null
     */
    public $availableTo = null;

    /**
     * @var int
     */
    protected $fileCounter;

    /**
     * @var NetexImporterBase
     */
    protected $sharedImporter = null;

    /**
     * @var Import;
     */
    protected $import = null;

    /**
     * @var callable[]
     */
    protected $processedHandlers = [];

    public function __construct(protected RouteSet $set)
    {
        //
    }

    /**
     * Import full set of NeTEx data.
     */
    public function importSet(): RouteImporter
    {
        $this->fileCounter = 0;
        Log::info(sprintf("[RouteImporter]: Importing NeTEx files in directory: %s", $this->set->getPath()));
        foreach (self::$tables as $tableName) {
            DB::table($tableName)->truncate();
        }

        $this->import = Import::create([
            'path' => $this->set->getPath(),
            'md5' => $this->set->getMd5(),
            'size' => $this->set->getSize(),
            'files' => count($this->set->getFiles()),
            'import_status' => 'importing',
            'message' => 'Importing core netex data.',
        ]);

        try {
            foreach ($this->set->getSharedFiles() as $sharedFile) {
                Log::debug(sprintf('[RouteImporter]: Importing shared file: %s', basename($sharedFile)));
                $this->sharedImporter = NetexSharedImporter::importFile($sharedFile);
                $this->onFileImport($this->sharedImporter);
            }

            foreach ($this->set->getLineFiles() as $lineFile) {
                Log::debug(sprintf('[RouteImporter]: Importing line file: %s', basename($lineFile)));
                $this->onFileImport(NetexLineImporter::importFile($lineFile));
            }
            $this->finalizeImport();
        } catch (Exception $except) {
            $this->import->fill(['import_status' => 'error', 'message' => $except->getMessage()])->save();
            throw $except;
        }
        Log::info(sprintf("[RouteImporter]: Successfully imported %d files", $this->fileCounter));
        return $this;
    }

    /**
     * True if set is already imported and set to current.
     *
     * @return bool
     */
    public function isImported(): bool
    {
        $lastImport = Import::latest()->first();
        if (!$lastImport) {
            Log::notice("[RouteImporter]: No previous import exist!");
            return false;
        }
        if ($lastImport->import_status !== 'imported') {
            Log::notice("[RouteImporter]: Last import was not a success!");
            return false;
        }
        return $lastImport->md5 === $this->set->getMd5();
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    public function addProcessedHandler(callable $callback): RouteImporter
    {
        $this->processedHandlers[] = $callback;
        return $this;
    }

    protected function finalizeImport(): void
    {
        if ($this->sharedImporter === null || !$this->availableFrom || !$this->availableTo) {
            throw new Exception("Shared data file is missing or lacks vital info");
        }
        $this->import->fill([
            'available_from' => $this->availableFrom,
            'available_to' =>  $this->availableTo,
            'version' => $this->sharedImporter->version,
            'import_status' => 'imported',
            'message' => null,
        ])->save();
    }

    protected function onFileImport(NetexImporterBase $importer): void
    {
        $this->fileCounter++;
        if (!$this->availableFrom || $this->availableFrom->isAfter($importer->availableFrom)) {
            $this->availableFrom = new Carbon($importer->availableFrom);
        }
        if (!$this->availableTo || $this->availableTo->isBefore($importer->availableTo)) {
            $this->availableTo = new Carbon($importer->availableTo);
        }
        $this->invokeProcessedHandlers($importer);
    }

    protected function invokeProcessedHandlers(NetexImporterBase $importer)
    {
        foreach ($this->processedHandlers as $callback) {
            call_user_func($callback, $importer, $this->fileCounter);
        }
    }
}
