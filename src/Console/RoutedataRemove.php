<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Models\ActiveStatus;
use TromsFylkestrafikk\Netex\Console\Traits\LogAndPrint;

class RoutedataRemove extends Command
{
    use LogAndPrint;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-remove
                            {--id= : Remove this route set}
                            {--path= : Remove route set(s) matching this path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove raw xml route data by ID or name. No options removes unused sets.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $success = true;
        $this->setLogPrefix('[NeTEx delete]: ');
        if ($this->option('id')) {
            $success = $this->removeById((int) $this->option('id'));
        }
        if ($this->option('path')) {
            $success = $success && $this->removeByPath($this->option('path'));
        }
        if (!$this->option('id') && !$this->option('path')) {
            $success = $success && $this->removeUnused();
        }
        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Search for unused route data and remove them.
     *
     * That is, all route sets that isn't part of activated sets, and the last
     * set.
     *
     * @return bool
     */
    protected function removeUnused(): bool
    {
        $last = Import::latest()->first();
        if (!$last) {
            $this->lpNotice("No route set found. Aborting");
            return false;
        }
        return Import::whereNotIn(
            'id',
            ActiveStatus::select('import_id')
                ->distinct()
                ->pluck('import_id')
                ->push($last->id)
                ->unique()
        )->get()->reduce(fn ($status, $import) => $status && $this->removeSet($import), true);
    }

    /**
     * Remove route set by ID.
     *
     * @param int $importId Import ID.
     * @return bool
     */
    protected function removeById(int $importId): bool
    {
        $import = Import::find($importId);
        if (!$import) {
            $this->lpWarning(sprintf("Route set with ID %s was not found", $this->option('id')));
            return false;
        }
        return $this->removeSet($import);
    }

    /**
     * Remove sets matching given path
     *
     * @param string $path
     * @return bool
     */
    protected function removeByPath(string $path): bool
    {
        return Import::wherePath($path)
            ->get()
            ->reduce(fn ($status, $import) => $status && $this->removeSet($import), true);
    }

    /**
     * Remove route set model and from disk if unique among imports.
     *
     * @param Import $import
     * @return bool
     */
    protected function removeSet(Import $import): bool
    {
        $success = true;
        $this->lpInfo(sprintf("Deleting set ID %d: '%s'", $import->id, $import->path));
        if (!$this->otherPathExists($import)) {
            $this->lpInfo(sprintf("Removing folder '%s'", $import->path));
            $success = Storage::disk(config('netex.disk'))->deleteDirectory($import->path);
            if (!$success) {
                $this->warn("Failed to delete route data directory {$import->path}");
            }
        }
        return $import->delete() && $success;
    }

    /**
     * True if other sets use the same path.
     *
     * @param Import $import
     * @return bool
     */
    protected function otherPathExists(Import $import): bool
    {
        return Import::wherePath($import->path)->where('id', '<>', $import->id)->count() > 0;
    }
}
