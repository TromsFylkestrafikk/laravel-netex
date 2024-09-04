<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;
use TromsFylkestrafikk\Netex\Services\RouteImporter\SharedFileImporter;

/**
 * Heavy lifting tool for stuffing NeTEx data into sql tables.
 */
class RouteImporter
{
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
            SharedFileImporter::importFile($sharedFile);
        }

        // foreach ($this->set->getLineFiles() as $lineFile) {
        //     $this->importLineData($lineFile);
        // }
        return $this;
    }
}
