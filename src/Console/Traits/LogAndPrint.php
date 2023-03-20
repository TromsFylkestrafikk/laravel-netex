<?php

namespace TromsFylkestrafikk\Netex\Console\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Send messages to both console output and Log.
 */
trait LogAndPrint
{
    protected $logPrefix = 'NeTEx: ';

    protected static $printMethods = [
        'alert' => 'alert',
        'error' => 'error',
        'warning' => 'warn',
        'notice' => 'info',
        'info' => 'line',
        'debug' => 'comment',
    ];

    /**
     * @param string $prefix
     * @return void
     */
    public function setLogPrefix(string $prefix): void
    {
        $this->logPrefix = $prefix;
    }

    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    public function lPrint(string $level, string $message): void
    {
        Log::$level(sprintf("%s%s", $this->logPrefix, $message));
        $this->{self::$printMethods[$level]}($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function lpAlert(string $message): void
    {
        $this->lPrint('alert', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function lpError(string $message): void
    {
        $this->lPrint('error', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function lpWarning(string $message): void
    {
        $this->lPrint('warning', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function lpNotice(string $message): void
    {
        $this->lPrint('notice', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function lpInfo(string $message): void
    {
        $this->lPrint('info', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function lpDebug(string $message): void
    {
        $this->lPrint('debug', $message);
    }
}
