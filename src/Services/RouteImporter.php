<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;

/**
 * Heavy lifting tools for stuffing NeTEx data into sql tables.
 */
class RouteImporter
{
    public $description = '';
    public $availableFrom = null;
    public $availableTo = null;
    public $version = null;

    /**
     * @var DbBulkInsert[]
     */
    protected $dumpers = [];

    /**
     * List of namespaces in currently parsed XML
     */
    protected $namespaces = [];

    public function __construct(protected RouteSet $set)
    {
        //
    }

    /**
     * Import full set of NeTEx data.
     */
    public function importSet(): RouteImporter
    {
        foreach ($this->set->getSharedFiles() as $sharedFile) {
            $this->importSharedData($sharedFile);
        }

        foreach ($this->set->getLineFiles() as $lineFile) {
            $this->importLineData($lineFile);
        }
        return $this;
    }

    /**
     * Import this XML file as shared NeTEx route data.
     *
     * @param string $xmlFile Full path of XML file
     */
    public function importSharedData(string $xmlFile): RouteImporter
    {
        $elementToTable = [
            'Calendar' => 'netex_calendar',
            'Operator' => 'netex_operators',
            'GroupOfLines' => 'netex_line_groups',
            'DestinationDisplay' => 'netex_destination_displays',
            'ScheduledStopPoint' => 'netex_stop_points',
            'ServiceLink' => 'netex_service_links',
        ];
        foreach ($elementToTable as $element => $table) {
            DB::table($table)->truncate();
            $this->dumpers[$element] = new DbBulkInsert($table);
        }
        $reader = new ChristmasTreeParser();
        $reader->open($xmlFile);
        $reader->addCallback(
            ['PublicationDelivery', 'dataObjects', 'CompositeFrame', 'validityConditions', 'AvailabilityCondition'],
            function (ChristmasTreeParser $reader) {
                $xml = $reader->expandSimpleXml();
                $this->availableFrom = $xml->FromDate;
                $this->availableTo = $xml->ToDate;
            }
        )->withParents(
            ['PublicationDelivery', 'dataObjects', 'CompositeFrame', 'frames'],
            function (ChristmasTreeParser $reader) {
                // List of element paths to read. The last element must have a
                // corresponding reader method. So if the last element is
                // 'Operator', there must be a method named 'readOperator())'
                $underFrames = [
                    ['ResourceFrame', 'organisations', 'Operator'],
                    ['ServiceFrame', 'Network', 'groupsOfLines', 'GroupOfLines'],
                    ['ServiceFrame', 'destinationDisplays', 'DestinationDisplay'],
                    ['ServiceFrame', 'scheduledStopPoints', 'ScheduledStopPoint'],
                    ['ServiceFrame', 'serviceLinks', 'ServiceLink'],
                ];
                foreach ($underFrames as $children) {
                    $leafElement = end($children);
                    if (method_exists($this, "read$leafElement")) {
                        $reader->addCallback($children, function (ChristmasTreeParser $reader) use ($leafElement) {
                            $xml = $reader->expandSimpleXml();
                            $this->{"read$leafElement"}($reader, $xml);
                        });
                    }
                }
            }
        )->parse();

        foreach ($this->dumpers as $dumper) {
            /** @var DbBulkInsert $dumper */
            $dumper->flush();
        }
        return $this;
    }

    public function importLineData(string $xmlFile): RouteImporter
    {
        return $this;
    }

    protected function readOperator(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['Operator']->addRecord([
            'id' => $xml['id'],
            'company_number' => $xml->CompanyNumber,
            'legal_name' => $xml->LegalName,
            'name' => $xml->Name,
        ]);
    }

    protected function readGroupOfLines(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['GroupOfLines']->addRecord([
            'id' => $xml['id'],
            'name' => $xml->Name,
        ]);
    }

    protected function readDestinationDisplay(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['DestinationDisplay']->addRecord([
            'id' => $xml['id'],
            'front_text' => $xml->FrontText,
        ]);
    }

    protected function readScheduledStopPoint(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['ScheduledStopPoint']->addRecord([
            'id' => $xml['id'],
            'name' => $xml->Name,
        ]);
    }

    protected function readServiceLink(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        if (!isset($xml->projections->LinkSequenceProjection)) {
            return;
        }
        $proj = $xml->projections->LinkSequenceProjection->children('http://www.opengis.net/gml/3.2');
        if ($proj->count() < 1) {
            return;
        }
        $this->dumpers['ServiceLink']->addRecord([
            'id' => $xml['id'],
            'distance' => $xml->Distance,
            'srs_dimension' => $proj->LineString->posList->attributes()['srsDimension'],
            'count' => $proj->LineString->posList->attributes()['count'],
            'pos_list' => $proj->LineString->posList,
        ]);
    }

    protected function read(ChristmasTreeParser $reader, SimpleXMLElement $xml): void
    {
        $this->dumpers['']->addRecord([
            'id' => $xml['id'],
        ]);
    }
}
