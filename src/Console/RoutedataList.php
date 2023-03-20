<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use TromsFylkestrafikk\Netex\Models\Import;

class RoutedataList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List NeTEx route data imports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $imports = Import::select(['id', 'path', 'version', 'size', 'import_status', 'created_at'])
            ->orderBy('id')
            ->get()
            ->toArray();
        $imports = array_map(function ($item) {
            $item['created_at'] = (new Carbon($item['created_at']))->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
            return $item;
        }, $imports);

        $this->table(['ID', 'path', 'version', 'size', 'status', 'created'], $imports);
        return Command::SUCCESS;
    }
}
