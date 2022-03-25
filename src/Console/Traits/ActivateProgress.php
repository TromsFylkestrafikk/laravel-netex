<?php

namespace TromsFylkestrafikk\Netex\Console\Traits;

use Illuminate\Support\Carbon;
use Symfony\Component\Console\Helper\ProgressBar;

trait ActivateProgress
{
    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar = null;

    protected function setupProgressBar()
    {
        $fromDate = new Carbon($this->activator->getFromDate());
        $toDate = new Carbon($this->activator->getToDate());
        $days = $fromDate->diffInDays($toDate) + 1;
        ProgressBar::setFormatDefinition('custom', " %current%/%max% [%bar%] %percent:3s%% %message%\n Remaining: %estimated:-6s% \n");
        $this->progressBar = $this->output->createProgressBar($days);
        $this->progressBar->setFormat('custom');
        $this->progressBar->setMessage($this->activator->getFromDate());
        $this->progressBar->start();
    }
}
