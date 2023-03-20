<?php

namespace TromsFylkestrafikk\Netex\Console;

use DateInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use TromsFylkestrafikk\Netex\Console\Traits\ActivateProgress;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

class RoutedataActivate extends Command
{
    use ActivateProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-activate
                            {from-date? : Activate data from this date}
                            {to-date? : Activate route data up until this date}
                            {--f|force : Force re-activation even if activation already exists}
                            {--m|missing : Activate only missing dates within period}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate netex route data for faster and more direct queries';

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteActivator
     */
    protected $activator;

    /**
     * Route set import model used during activation.
     *
     * @var \TromsFylkestrafikk\Netex\Models\Import
     */
    protected $import;

    /**
     * @var int
     */
    protected $journeyCount;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->import = Import::imported()->latest()->first();
        if (!$this->import) {
            $this->error('No valid route set found. Add route set with artisan netex:import-routedata.');
            return self::FAILURE;
        }
        $this->setupActivator();
        $this->info(sprintf(
            'Activating route data between %s and %s',
            $this->activator->getFromDate(),
            $this->activator->getToDate()
        ));

        $this->activate();

        $stats = $this->activator->summary();
        $this->info(sprintf(
            "Activation complete. %d days, %d journeys, %d calls",
            $stats['days'],
            $stats['journeys'],
            $stats['calls']
        ));
        if ($stats['errors']) {
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * Initialize activator and set its date range
     */
    protected function setupActivator(): void
    {
        $fromDate = $this->argument('from-date') ?: today()->format('Y-m-d');
        $toDate = $this->argument('to-date') ?: (new Carbon($fromDate))
            ->add(new DateInterval(config('netex.activation_period')))
            ->format('Y-m-d');
        $this->activator = new RouteActivator($this->import, $fromDate, $toDate);
    }

    /**
     * Artisan wrapper around RouteActivator::activate()
     *
     * @return void
     */
    protected function activate(): void
    {
        $this->info("Activating ...");
        $this->setupProgressBar();
        $this->activator
            ->force($this->option('force'))
            ->missingOnly($this->option('missing'))
            ->onJourney(fn () => $this->journeyCount++)
            ->onDay(function ($date, $result) {
                $this->progressBar->advance();
                $this->progressBar->setMessage(
                    sprintf("%s: %s (%d journeys)", $date, $result, $this->journeyCount)
                );
                $this->progressBar->display();
                $this->journeyCount = 0;
            })
            ->activate();
        $this->progressBar->finish();
    }
}
