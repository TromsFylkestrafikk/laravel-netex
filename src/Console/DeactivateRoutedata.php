<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Helper\ProgressBar;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

class DeactivateRoutedata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:deactivate
                            {from-date? : De-activate data from this date}
                            {to-date? : De-activate route data to this date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'De-activate route data.';

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteActivator
     */
    protected $activator;

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar = null;

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
        $this->activator = new RouteActivator($this->argument('from-date'), $this->argument('to-date'));
        $this->info(sprintf(
            "De-activating routedata between %s and %s",
            $this->activator->getFromDate(),
            $this->activator->getToDate()
        ));
        $this->setupProgressBar();
        $this->activator
            ->onDay(function ($date) {
                $this->progressBar->advance();
                $this->progressBar->setMessage($date);
            })
            ->deactivate();
        $this->progressBar->finish();
        return self::SUCCESS;
    }

    protected function setupProgressBar()
    {
        $fromDate = new Carbon($this->activator->getFromDate());
        $toDate = new Carbon($this->activator->getToDate());
        $days = $fromDate->diffInDays($toDate);
        ProgressBar::setFormatDefinition('custom', " %current%/%max% [%bar%] %percent:3s%% %message%\n Remaining: %estimated:-6s% \n");
        $this->progressBar = $this->output->createProgressBar($days);
        $this->progressBar->setFormat('custom');
        $this->progressBar->setMessage($this->activator->getFromDate());
        $this->progressBar->start();
    }
}
