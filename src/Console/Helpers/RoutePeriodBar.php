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
    protected $barWidth = 80;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $maxDate;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $passiveFrom;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $passiveTo;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $activeFrom;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $activeTo;

    /**
     * @var int
     */
    protected $passiveDayStart;

    /**
     * @var int
     */
    protected $passiveDayEnd;

    /**
     * @var int
     */
    protected $activeDayStart;

    /**
     * @var int
     */
    protected $activeDayEnd;

    /**
     * @var string
     */
    protected $passiveBar;

    /**
     * @var string
     */
    protected $activeBar;

    /**
     * @var int
     */
    protected $days;

    protected $strPos = 0;

    public function __construct(RouteActivator $passive, RouteActivator $active)
    {
        $this->passive = $passive;
        $this->active = $active;

        $this->passiveFrom = new Carbon($passive->getFromDate());
        $this->passiveTo = new Carbon($passive->getToDate());
        $this->activeFrom = new Carbon($active->getFromDate());
        $this->activeTo = new Carbon($active->getToDate());

        $this->minDate = $this->passiveFrom < $this->activeFrom ? $this->passiveFrom : $this->activeFrom;
        $this->maxDate = $this->passiveTo > $this->activeTo ? $this->passiveTo : $this->activeTo;
        $this->days = (int) $this->minDate->diffInDays($this->maxDate);
        $this->passiveDayStart = $this->minDate->diffInDays($this->passiveFrom);
        $this->passiveDayEnd = $this->minDate->diffInDays($this->passiveTo);
        $this->activeDayStart = $this->minDate->diffInDays($this->activeFrom);
        $this->activeDayEnd = $this->minDate->diffInDays($this->activeTo);
        $this->passiveBar = '';
        $this->activeBar = '';
    }

    public function bars()
    {
        $passiveLabelBar = str_pad('', $this->barWidth, ' ');
        $this->putStrCenteredAround($passiveLabelBar, $this->passiveFrom->format('Y-m-d'), $this->passiveDayStart);
        $this->putStrCenteredAround($passiveLabelBar, $this->passiveTo->format('Y-m-d'), $this->passiveDayEnd);
        $this->strPos = 0;
        $activeLabelBar = str_pad('', $this->barWidth, ' ');
        $this->putStrCenteredAround($activeLabelBar, $this->activeFrom->format('Y-m-d'), $this->activeDayStart);
        $this->putStrCenteredAround($activeLabelBar, $this->activeTo->format('Y-m-d'), $this->activeDayEnd);
        $this->strPos = 0;
        $bars  = sprintf("               %s\n", $passiveLabelBar);
        $bars .= sprintf("NeTEx period:  |%s|\n", $this->drawBar($this->passiveDayStart, $this->passiveDayEnd));
        $bars .= sprintf("Active period: |%s|\n", $this->drawBar($this->activeDayStart, $this->activeDayEnd));
        $bars .= sprintf("               %s\n", $activeLabelBar);
        $this->strPos = 0;
        return $bars;
    }

    protected function drawBar($startDay, $endDay)
    {
        $bar = $this->drawDays($startDay);
        $bar .= $this->drawDays($endDay - $startDay, '#');
        $bar .= $this->drawDays($this->days - $endDay);
        return $bar;
    }

    protected function drawDays($days, $char = '-')
    {
        return str_pad('', $this->daysToLength($days), $char);
    }

    protected function putStrCenteredAround(&$bar, $string, $dayPos)
    {
        $startPos = $this->daysToLength($dayPos);
        $strScew = (int) (strlen($string) / 2);
        $startPos = max(0, $startPos - $strScew, $this->strPos);

        $this->drawInStr($bar, $string, $startPos);
        $this->strPos = $startPos + strlen($string) + 1;
    }
    protected function drawInStr(&$str, $insert, $fromPos = 0)
    {
        foreach (str_split($insert) as $pos => $ch) {
            $str[$fromPos + $pos] = $ch;
        }
    }

    protected function daysToLength($day): int
    {
        return (int) round($this->barWidth * $day / $this->days);
    }
}
