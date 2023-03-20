<?php

namespace TromsFylkestrafikk\Netex\Console\NeTEx;

use DOMDocument;
use DateInterval;
use DateTime;
use Exception;
use XMLReader;

class NetexFileParser
{
    public $description = '';
    public $availableFrom = null;
    public $availableTo = null;
    public $operators = [];
    public $scheduledStopPoints = [];
    public $operatingPeriods = [];
    public $dayTypeAssignments = [];
    public $dayTypes = [];
    public $calendar = [];
    public $groupOfLines = [];
    public $serviceLinks = [];
    public $routePoints = [];
    public $destinationDisplays = [];
    public $stopAssignments = [];
    public $vehicleSchedules = [];
    public $version = null;

    // XML data
    public $lineDescription = '';
    public $lines = [];
    public $routes = [];
    public $journeyPatterns = [];
    public $vehicleJourneys = [];

    protected $path;

    /**
     * Constructor.
     *
     * @param  string  $path
     *   Full or relative path to main NeTEx file
     */
    public function __construct($path)
    {
        // Main XML data
        $this->path = $path;
    }

    /**
     * Parse main NeTEx (XML) file.
     */
    public function parseMainXmlFile()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $xml = new XMLReader();
        $xml->open($this->path);

        while ($xml->read()) {
            if ($xml->nodeType == XMLReader::ELEMENT) {
                switch ($xml->name) {
                    case 'Description':
                        $xml->read();
                        $this->description = $xml->value;
                        break;

                    case 'AvailabilityCondition':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $this->availableFrom = $sxml->FromDate;
                        $this->availableTo = $sxml->ToDate;
                        break;
                    case 'Operator':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $this->operators[$id]['CompanyNumber'] = (int) $sxml->CompanyNumber;
                        $this->operators[$id]['LegalName'] = (string) $sxml->LegalName;
                        $this->operators[$id]['Name'] = (string) $sxml->Name;
                        break;

                    case 'GroupOfLines':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID($sxml->attributes()->id);
                        $this->groupOfLines[$id]['Name'] = (string) $sxml->Name;
                        break;

                    case 'RoutePoint':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID($sxml->attributes()->id);
                        // Hack, but pick a random element and get the Trapeze
                        // export version of this route set.
                        if ($this->version === null) {
                            $this->version = (string) $sxml->attributes()->version;
                        }
                        $this->routePoints[$id]['ProjectedPointRef'] = (string) $sxml->projections->PointProjection->ProjectedPointRef->attributes()->ref;
                        break;

                    case 'DestinationDisplay':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = (int) $this->trimID($sxml['id']);
                        $this->destinationDisplays[$id]['FrontText'] = (string) $sxml->FrontText;
                        break;

                    case 'ScheduledStopPoint':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $this->scheduledStopPoints[$id]['Name'] = (string) $sxml->Name;
                        break;

                    case 'ServiceLink':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $ns = $sxml->getNamespaces(true);
                        $posList = '';
                        $posCount = 0;
                        $posDimension = 0;

                        if (isset($sxml->projections->LinkSequenceProjection) && isset($ns['gis'])) {
                            $gis = $sxml->projections->LinkSequenceProjection->children($ns['gis'])->LineString->posList;
                            $posDimension = (int) $gis->attributes()->srsDimension;
                            $posCount = (int) $gis->attributes()->count;
                            $posList = (string) $gis;
                        }

                        $this->serviceLinks[$id]['Distance'] = (float) $sxml->Distance;
                        $this->serviceLinks[$id]['srsDimension'] = $posDimension;
                        $this->serviceLinks[$id]['count'] = $posCount;
                        $this->serviceLinks[$id]['posList'] = $posList;
                        break;

                    case 'PassengerStopAssignment':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID($sxml->attributes()->id);
                        $this->stopAssignments[$id]['order'] = (int) $sxml->attributes()->order;
                        $this->stopAssignments[$id]['ScheduledStopPointRef'] = $this->trimID($sxml->ScheduledStopPointRef->attributes()->ref);
                        $this->stopAssignments[$id]['QuayRef'] = (string) $sxml->QuayRef->attributes()->ref;
                        break;

                    case 'Notice':
                        break;

                    case 'Block':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID($sxml->attributes()->id);
                        $this->vehicleSchedules[$id]['BlockRef'] = $id;
                        $this->vehicleSchedules[$id]['PrivateCode'] = (int) $sxml->PrivateCode;
                        $this->vehicleSchedules[$id]['DayTypeRef'] = $this->trimID($sxml->dayTypes->DayTypeRef->attributes()->ref);
                        $journeys = [];

                        foreach ($sxml->journeys->VehicleJourneyRef as $journeyRef) {
                            $journeys[] = ['VehicleJourneyRef' => $this->trimID($journeyRef->attributes()->ref)];
                        }

                        $this->vehicleSchedules[$id]['journeys'] = $journeys;
                        break;

                    case 'DayType':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $daysOfWeek = null;

                        if (isset($sxml->properties)) {
                            $daysOfWeek = (string) $sxml->properties->PropertyOfDay->DaysOfWeek;
                        }

                        $this->dayTypes[$id]['DaysOfWeek'] = $daysOfWeek;
                        break;

                    case 'OperatingPeriod':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = (string) $sxml->attributes()->id;
                        $this->operatingPeriods[$id]['FromDate'] = (string) $sxml->FromDate;
                        $this->operatingPeriods[$id]['ToDate'] = (string) $sxml->ToDate;
                        break;

                    case 'DayTypeAssignment':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $operatingPeriodRef = null;

                        if (isset($sxml->OperatingPeriodRef)) {
                            $operatingPeriodRef = (string) $sxml->OperatingPeriodRef->attributes()->ref;
                        }

                        $this->dayTypeAssignments[$id]['order'] = (int) $sxml->attributes()->order;
                        $this->dayTypeAssignments[$id]['isAvailable'] = ((string) $sxml->isAvailable !== 'false');
                        $this->dayTypeAssignments[$id]['OperatingPeriodRef'] = $operatingPeriodRef;
                        $this->dayTypeAssignments[$id]['DayTypeRef'] = $this->trimID((string) $sxml->DayTypeRef->attributes()->ref);
                        $this->dayTypeAssignments[$id]['Date'] = (string) $sxml->Date;
                        break;

                    default:
                        break;
                }
            }
        }
        $xml->close();
    }

    /**
     * Generate calendar from operatingPeriods, dayTypeAssignments and dayTypes.
     */
    public function generateCalendar()
    {
        foreach ($this->dayTypes as $id => $dt) {
            $calendarID = (string) $id;

            if ($dt['DaysOfWeek'] !== null) {
                // Periods.
                foreach ($this->dayTypeAssignments as $key => $dta) {
                    if (($dta['DayTypeRef'] === $calendarID) && ($dta['OperatingPeriodRef'] !== null)) {
                        // Split time period into individual dates.
                        $opID = $dta['OperatingPeriodRef'];
                        $fromDate = $this->operatingPeriods[$opID]['FromDate'];
                        $toDate = $this->operatingPeriods[$opID]['ToDate'];
                        $dates = $this->getActiveDaysInPeriod($fromDate, $toDate, $dt['DaysOfWeek']);

                        foreach ($dates as $date) {
                            $calID = $calendarID . $date;

                            $this->calendar[$calID]['date'] = $date;
                            $this->calendar[$calID]['ref'] = $id;
                        }
                    }
                }

                // Extra or unavailable dates.
                foreach ($this->dayTypeAssignments as $key => $dta) {
                    if ($dta['DayTypeRef'] === $calendarID) {
                        if ($dta['isAvailable'] === false) {
                            // Delete calendar entry.
                            $calID = $calendarID . $dta['Date'];
                            unset($this->calendar[$calID]);
                        } elseif ($dta['OperatingPeriodRef'] === null) {
                            // Add single date.
                            $calID = $calendarID . $dta['Date'];

                            $this->calendar[$calID]['date'] = $dta['Date'];
                            $this->calendar[$calID]['ref'] = $id;
                        }
                    }
                }
            } else {
                // Single date.
                foreach ($this->dayTypeAssignments as $key => $dta) {
                    if ($dta['DayTypeRef'] === $calendarID) {
                        // Add single date.
                        $calID = $calendarID . $dta['Date'];

                        $this->calendar[$calID]['date'] = $dta['Date'];
                        $this->calendar[$calID]['ref'] = $id;
                    }
                }
            }
        }
    }

    /**
     * Parse NeTEx (XML) line file.
     *
     * @param  string  $filename
     *   Full or relative path to NeTEx line file
     */
    public function parseLineXmlFile($filename)
    {
        $this->lineDescription = '';
        $this->routes = [];
        $this->journeyPatterns = [];
        $this->vehicleJourneys = [];

        $doc = new DOMDocument('1.0', 'UTF-8');
        $xml = new XMLReader();
        $xml->open($filename);

        while ($xml->read()) {
            if ($xml->nodeType == XMLReader::ELEMENT) {
                switch ($xml->name) {
                    case 'Description':
                        $xml->read();
                        $this->lineDescription = $xml->value;
                        break;

                    case 'Line':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $operatorRef = '1';   // Unknown line operator.

                        if (isset($sxml->OperatorRef)) {
                            $operatorRef = $this->trimID($sxml->OperatorRef->attributes()->ref);
                        }

                        $this->lines[$id]['Name'] = (string) $sxml->Name;
                        $this->lines[$id]['TransportMode'] = (string) $sxml->TransportMode;
                        $this->lines[$id]['TransportSubmode'] = (string) $sxml->TransportSubmode->children()[0];
                        $this->lines[$id]['PublicCode'] = (string) $sxml->PublicCode;
                        $this->lines[$id]['PrivateCode'] = (int) $sxml->PrivateCode;
                        $this->lines[$id]['OperatorRef'] = $operatorRef;
                        $this->lines[$id]['RepresentedByGroupRef'] = $this->trimID($sxml->RepresentedByGroupRef->attributes()->ref);
                        break;

                    case 'Route':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID($sxml->attributes()->id);
                        $this->routes[$id]['Name'] = $sxml->Name;
                        $this->routes[$id]['ShortName'] = $sxml->ShortName;
                        $this->routes[$id]['LineRef'] = $this->trimID($sxml->LineRef->attributes()->ref);
                        $this->routes[$id]['DirectionType'] = $sxml->DirectionType;
                        $points = [];

                        foreach ($sxml->pointsInSequence->PointOnRoute as $point) {
                            $points[] = [
                                'order' => (int) $point->attributes()->order,
                                'RoutePointRef' => $this->trimID($point->RoutePointRef->attributes()->ref)
                            ];
                        }

                        $this->routes[$id]['pointsInSequence'] = $points;
                        break;

                    case 'JourneyPattern':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID($sxml->attributes()->id);
                        $this->journeyPatterns[$id]['Name'] = $sxml->Name;
                        $this->journeyPatterns[$id]['RouteRef'] = $this->trimID($sxml->RouteRef->attributes()->ref);
                        $points = [];
                        $links = [];

                        foreach ($sxml->pointsInSequence->StopPointInJourneyPattern as $point) {
                            $points[] = [
                                'id' => $this->trimID($point['id']),
                                'order' => (int) $point['order'],
                                'ScheduledStopPointRef' => $this->trimID($point->ScheduledStopPointRef['ref']),
                                'ForAlighting' => (int) ((string) $point->ForAlighting !== 'false'),
                                'ForBoarding' => (int) ((string) $point->ForBoarding !== 'false'),
                                'DestinationDisplayRef' => $point->DestinationDisplayRef
                                    ? ((int) $this->trimID($point->DestinationDisplayRef['ref']))
                                    : null,
                            ];
                        }

                        foreach ($sxml->linksInSequence->ServiceLinkInJourneyPattern as $link) {
                            $links[] = [
                                'id' => $this->trimID($link->attributes()->id),
                                'order' => (int) $link->attributes()->order,
                                'ServiceLinkRef' => $this->trimID($link->ServiceLinkRef->attributes()->ref)
                            ];
                        }

                        $this->journeyPatterns[$id]['pointsInSequence'] = $points;
                        $this->journeyPatterns[$id]['linksInSequence'] = $links;
                        break;

                    case 'ServiceJourney':
                        $sxml = simplexml_import_dom($doc->importNode($xml->expand(), true));
                        $id = $this->trimID((string) $sxml->attributes()->id);
                        $this->vehicleJourneys[$id]['Name'] = $sxml->Name;
                        $this->vehicleJourneys[$id]['PrivateCode'] = (int) $sxml->PrivateCode;
                        $this->vehicleJourneys[$id]['JourneyPatternRef'] = $this->trimID($sxml->JourneyPatternRef->attributes()->ref);
                        $this->vehicleJourneys[$id]['OperatorRef'] = $this->trimID($sxml->OperatorRef->attributes()->ref);
                        $this->vehicleJourneys[$id]['LineRef'] = $this->trimID($sxml->LineRef->attributes()->ref);
                        $this->vehicleJourneys[$id]['DayTypeRef'] = $this->trimID($sxml->dayTypes->DayTypeRef->attributes()->ref);
                        $timeTable = [];

                        foreach ($sxml->passingTimes->TimetabledPassingTime as $pt) {
                            $timeTable[] = [
                                'ArrivalTime' => $pt->ArrivalTime ? (string) $pt->ArrivalTime : null,
                                'DepartureTime' => $pt->DepartureTime ? (string) $pt->DepartureTime : null,
                                'StopPointInJourneyPatternRef' => $this->trimID((string) $pt->StopPointInJourneyPatternRef->attributes()->ref)
                            ];
                        }

                        $this->vehicleJourneys[$id]['passingTimes'] = $timeTable;
                        break;

                    default:
                        break;
                }
            }
        }

        $xml->close();
    }

    /**
     * Reset parsed data.
     *
     * @return void
     */
    public function reset(): void
    {
        unset($this->calendar);
        unset($this->operatingPeriods);
        unset($this->dayTypeAssignments);
        unset($this->dayTypes);
        unset($this->operators);
        unset($this->groupOfLines);
        unset($this->routePoints);
        unset($this->scheduledStopPoints);
        unset($this->stopAssignments);
        unset($this->destinationDisplays);
        unset($this->serviceLinks);
        unset($this->vehicleSchedules);
    }

    /**
     * Retrieve active days for the specified time period.
     *
     * @param  string  $from
     *   Date format: Y-m-d\TH:i:s
     * @param  string  $to
     *   Date format: Y-m-d\TH:i:s
     * @param  string  $daysOfWeek
     *   A string containing at least one of the following keywords:
     *   Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Weekdays
     * @return  array
     *   Date format: Y-m-d
     */
    protected function getActiveDaysInPeriod($from, $to, $daysOfWeek): array
    {
        $start = DateTime::createFromFormat('Y-m-d\TH:i:s', $from);
        $end = DateTime::createFromFormat('Y-m-d\TH:i:s', $to);

        if (!$start || !$end) {
            throw new Exception("Time period error! FROM: " . $from . ' TO: ' . $to);
        }

        $timestamp = $start->getTimestamp();
        $endTimestamp = $end->getTimestamp();
        $result = [];

        do {
            if ($this->isActiveDayOfWeek($timestamp, $daysOfWeek) === true) {
                array_push($result, date('Y-m-d', $timestamp));
            }

            $start->add(new DateInterval('P1D'));      // Add one day.
            $timestamp = $start->getTimestamp();
        } while ($timestamp < $endTimestamp);

        return $result;
    }

    /**
     * Check if a timestamp corresponds to one of the supplied weekdays.
     *
     * @param  integer  $timestamp
     *   A timestamp that represents a weekday.
     * @param  string  $daysOfWeek
     *   A string containing at least one of the following keywords:
     *   Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Weekdays
     * @return  bool
     *   Returns TRUE when a match is found within the supplied list.
     *   Returns FALSE when no match is found.
     */
    protected function isActiveDayOfWeek($timestamp, $daysOfWeek): bool
    {
        if (strpos($daysOfWeek, 'Weekdays') !== false) {
            $daysOfWeek .= 'MondayTuesdayWednesdayThursdayFriday';
        }

        $day = date("l", $timestamp);
        return (strpos($daysOfWeek, $day) !== false) ? true : false;
    }

    /**
     * Trim the supplied ID string.
     *
     * @param  string  $id
     *   A string with sections separated by colon (:)
     * @return  string
     *   Returns the last section of the input string
     */
    protected function trimID($id): string
    {
        $arr = explode(':', $id);
        return end($arr);
    }
}
