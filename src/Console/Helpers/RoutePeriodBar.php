<?php

namespace TromsFylkestrafikk\Netex\Console\Helpers;

use Illuminate\Support\Carbon;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

/**
 * Formats two horizontal bars with indication of route data presense.
 */
class RoutePeriodBar
{
    /**
     * @var RouteActivator
     */
    protected $passive;

    /**
     * @var RouteActivator
     */
    protected $active;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $minDate;

    /**
     * @var int
     */
    protected $barWith = 80;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $maxDate;

    protected $passiveDayStart;
    protected $passiveDayEnd;
    protected $activeDayStart;
    protected $activeDayEnd;

    protected $passiveBar;

    protected $activeBar;

    /**
     * @var int
     */
    protected $days;

    public function __construct(RouteActivator $passive, RouteActivator $active)
    {
        $this->passive = $passive;
        $this->active = $active;

        $passiveFrom = new Carbon($passive->getFromDate());
        $passiveTo = new Carbon($passive->getToDate());
        $activeFrom = new Carbon($active->getFromDate());
        $activeTo = new Carbon($active->getToDate());

        $this->minDate = $passiveFrom < $activeFrom ? $passiveFrom : $activeFrom;
        $this->maxDate = $passiveTo > $activeTo ? $passiveTo : $activeTo;
        $this->days = (int) $this->minDate->diffInDays($this->maxDate);
        $this->passiveDayStart = $this->minDate->diffInDays($passiveFrom);
        $this->passiveDayEnd = $this->maxDate->diffInDays($passiveTo);
        $this->activeDayStart = $this->minDate->diffInDays($activeFrom);
        $this->activeDayEnd = $this->maxDate->diffInDays($activeTo);
        $this->passiveBar = '';
        $this->activeBar = '';
    }

    public function bars()
    {
        $this->passiveBar = $this->drawBar($this->passiveDayStart, $this->passiveDayEnd);
        $this->activeBar =  $this->drawBar($this->activeDayStart, $this->activeDayEnd);
        return sprintf("|%s|\n|%s|", $this->passiveBar, $this->activeBar);
    }

    protected function drawBar($startDay, $endDay)
    {
        $bar = $this->drawDays($startDay);
        $bar .= $this->drawDays($endDay - $startDay, '#');
        $bar .= $this->drawDays($this->days - $endDay);
        return $bar;
    }

    public function drawDays($days, $char = '-')
    {
        $chars = round($this->barWith * $days / $this->days);
        return str_pad('', (int) $chars, $char);
    }
}
