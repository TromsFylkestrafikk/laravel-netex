<?php

namespace TromsFylkestrafikk\Netex\Services\RouteImporter;

use SimpleXMLElement;

/**
 * Import a NeTEx shared data file.
 */
class NetexLineImporter extends NetexImporterBase
{
    protected $frames = [
        'Route' => [
            'path' => ['ServiceFrame', 'routes', 'Route'],
            'table' => 'netex_routes',
        ],
        'PointOnRoute' => ['table' => 'netex_route_point_sequence'],
        'Line' => [
            'path' => ['ServiceFrame', 'lines', 'Line'],
            'table' => 'netex_lines',
        ],
        'JourneyPattern' => [
            'path' => ['ServiceFrame', 'journeyPatterns', 'JourneyPattern'],
            'table' => 'netex_journey_patterns',
        ],
        'JourneyPatternStopPoint' => ['table' => 'netex_journey_pattern_stop_point'],
        'JourneyPatternLinks' => ['table' => 'netex_journey_pattern_link'],
        'ServiceJourney' => [
            'path' => ['TimetableFrame', 'vehicleJourneys', 'ServiceJourney'],
            'table' => 'netex_vehicle_journeys',
        ],
        'JourneyDayType' => ['table' => 'netex_journey_day_types'],
        'DatedServiceJourney' => [
            'path' => ['TimetableFrame', 'vehicleJourneys', 'DatedServiceJourney'],
            'table' => 'netex_dated_service_journeys',
        ],
        'TimetabledPassingTime' => ['table' => 'netex_passing_times'],
        'NoticeAssignment' => [
            'path' => ['TimetableFrame', 'noticeAssignments', 'NoticeAssignment'],
            'table' => 'netex_notice_assignments',
        ],
    ];

    protected function readRoute(SimpleXMLElement $xml): array
    {
        $routeId = (string) $xml['id'];
        foreach ($xml->pointsInSequence->PointOnRoute as $point) {
            $this->dumpers['PointOnRoute']->addRecord([
                'route_ref' => $routeId,
                'order' => (int) $point['order'],
                'stop_point_ref' => $point->RoutePointRef['ref']
            ]);
        }
        return [
            'id' => $routeId,
            'name' => $xml->Name,
            'short_name' => $xml->ShortName,
            'line_ref' => $xml->LineRef,
            'direction' => $xml->DirectionType,
        ];
    }

    protected function readLine(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'name' => $xml->Name,
            'transport_mode' => $xml->TransportMode,
            'transport_submode' => $xml->TransportSubmode->children()[0],
            'public_code' => $xml->PublicCode,
            'private_code' => $xml->PrivateCode ?: ((int) $xml->PublicCode),
            'operator_ref' => isset($xml->OperatorRef) ? $xml->OperatorRef['ref'] : null,
            'line_group_ref' => $xml->RepresentedByGroupRef['ref'],
        ];
    }

    protected function readJourneyPattern(SimpleXMLElement $xml): array
    {
        $jpId = $xml['id'];

        foreach ($xml->pointsInSequence->StopPointInJourneyPattern as $point) {
            $this->dumpers['JourneyPatternStopPoint']->addRecord([
                'id' => $point['id'],
                'journey_pattern_ref' => $jpId,
                'order' => (int) $point['order'],
                'stop_point_ref' => (string) $point->ScheduledStopPointRef['ref'],
                'alighting' => (int) ((string) $point->ForAlighting !== 'false'),
                'boarding' => (int) ((string) $point->ForBoarding !== 'false'),
                'destination_display_ref' => $point->DestinationDisplayRef
                    ? ($point->DestinationDisplayRef['ref'])
                    : null,
            ]);
        }

        if ($xml->linksInSequence->ServiceLinkInJourneyPattern) {
            foreach ($xml->linksInSequence->ServiceLinkInJourneyPattern as $link) {
                $this->dumpers['JourneyPatternLinks']->addRecord([
                    'id' => $link['id'],
                    'journey_pattern_ref' => $jpId,
                    'order' => $link->attributes()->order,
                    'service_link_ref' => $link->ServiceLinkRef['ref'],
                ]);
            }
        }

        return [
            'id' => $jpId,
            'name' => $xml->Name,
            'route_ref' => $xml->RouteRef['ref'],
        ];
    }

    protected function readServiceJourney(SimpleXMLElement $xml): array|null
    {
        $journeyId = (string) $xml['id'];

        foreach ($xml->passingTimes->TimetabledPassingTime as $time) {
            $this->dumpers['TimetabledPassingTime']->addRecord([
                'vehicle_journey_ref' => $journeyId,
                'arrival_time' => $time->ArrivalTime ? $time->ArrivalTime : null,
                'departure_time' => $time->DepartureTime ? $time->DepartureTime : null,
                'journey_pattern_stop_point_ref' => $time->StopPointInJourneyPatternRef['ref'],
            ]);
        }

        if (isset($xml->dayTypes)) {
            foreach ($xml->dayTypes->DayTypeRef as $dayTypeRef) {
                $this->dumpers['JourneyDayType']->addRecord([
                    'service_journey_id' => $journeyId,
                    'day_type_ref' => $dayTypeRef['ref'],
                ]);
            }
        }

        return [
            'id' => $journeyId,
            'name' => $xml->Name,
            'private_code' => (int) $xml->PrivateCode,
            'journey_pattern_ref' => $xml->JourneyPatternRef['ref'],
            'operator_ref' => $xml->OperatorRef['ref'],
            'line_ref' => $xml->LineRef['ref'],
        ];
    }

    protected function readDatedServiceJourney(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'service_journey_ref' => $xml->ServiceJourneyRef['ref'],
            'operating_day_ref' => $xml->OperatingDayRef['ref'],
        ];
    }

    protected function readNoticeAssignment(SimpleXMLElement $xml): array
    {
        return [
            'id' => $xml['id'],
            'notice_ref' => (string) $xml->NoticeRef['ref'],
            'notice_obj_ref' => (string) $xml->NoticedObjectRef['ref'],
        ];
    }
}
