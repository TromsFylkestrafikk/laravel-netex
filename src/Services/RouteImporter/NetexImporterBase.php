<?php

namespace TromsFylkestrafikk\Netex\Services\RouteImporter;

use TromsFylkestrafikk\Netex\Services\DbBulkInsert;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;

/**
 * Technical nitty-gritty between XML files and ChristmasTreeParser.
 */
abstract class NetexImporterBase
{
    public $description = null;
    public $availableFrom = null;
    public $availableTo = null;
    public $version = null;

    /**
     * List of codespaces in this file.
     *
     * @var array
     */
    public $codespaces = [];

    /**
     * List of elements under <frames> to act upon.
     *
     * The key is the method suffix name used for extraction of DB records. The
     * actual method name will then be "read" . $key. The method signature for
     * e.g. 'Company' as key will then be
     *
     *   protected function readCompany(\SimpleXMLElement $xml): array|null
     *
     * The value is a nested
     * array with two possible key/value pairs:
     *   - path => Array of XML children under <frames> to match for.
     *   - table => The database table used for storing the extracted data.
     *
     * Either one of these nested keys can be omitted. If only path is provided,
     * DB insertion after method callback won't be executed. If only table is
     * provided, this will simply create an additional DB dumper
     *
     * @var array
     */
    protected $frames = [];

    /**
     * @var DbBulkInsert[]
     */
    protected $dumpers = [];

    final public function __construct(protected string $xmlFile)
    {
    }

    /**
     * Get the very file this importer is working with.
     */
    public function getFile(): string
    {
        return $this->xmlFile;
    }

    /**
     * Shorthand constructor for importing a file.
     *
     * @param string $xmlFile Full path to XML file
     */
    public static function importFile(string $xmlFile): NetexImporterBase
    {
        return (new static($xmlFile))->import();
    }

    /**
     * Run import.
     */
    public function import(): NetexImporterBase
    {
        foreach ($this->frames as $name => $handler) {
            if (empty($handler['table'])) {
                continue;
            }
            $this->dumpers[$name] = new DbBulkInsert($handler['table']);
        }
        $reader = new ChristmasTreeParser();
        $reader->open($this->xmlFile);
        $this->mapCallbacks($reader)->addCallback(
            ['PublicationDelivery', 'dataObjects', 'CompositeFrame', 'validityConditions', 'AvailabilityCondition'],
            function (ChristmasTreeParser $reader) {
                $xml = $reader->expandSimpleXml();
                $this->availableFrom = (string) $xml->FromDate;
                $this->availableTo = (string) $xml->ToDate;
            }
        )->withParents(
            ['PublicationDelivery', 'dataObjects', 'CompositeFrame', 'frames'],
            fn ($reader) => $this->mapFramesReaders($reader)
        )->parse();

        foreach ($this->dumpers as $dumper) {
            /** @var DbBulkInsert $dumper */
            $dumper->flush();
        }
        return $this;
    }

    /**
     * Give implementations a chance to add additional callbacks.
     */
    protected function mapCallbacks(ChristmasTreeParser $reader): ChristmasTreeParser
    {
        return $reader;
    }

    protected function mapFramesReaders(ChristmasTreeParser $reader): void
    {
        foreach ($this->frames as $name => $handler) {
            if (!method_exists($this, "read$name") || empty($handler['path'])) {
                continue;
            }
            $reader->addCallback($handler['path'], function (ChristmasTreeParser $reader) use ($name) {
                $xml = $reader->expandSimpleXml();
                // Perform actual callback on matched XML element.
                $record = $this->{"read$name"}($xml);
                if (! $record || empty($this->frames[$name]['table'])) {
                    return;
                }
                $this->dumpers[$name]->addRecord($record);
            });
        }
    }
}
