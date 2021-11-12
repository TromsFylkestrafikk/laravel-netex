<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use TromsFylkestrafikk\Netex\Models\StopPlace;
use TromsFylkestrafikk\Netex\Models\GroupOfStopPlaces;
use TromsFylkestrafikk\Netex\Models\TopographicPlace;
use TromsFylkestrafikk\Netex\Models\StopQuay;
use TromsFylkestrafikk\Xml\ChristmasTreeParser;

class ImportStops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:importstops
                            {xml : Stop places in NeTEx format.}
                            {--k|keep : Keep existing data not seen in XML.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import stop places from XML file in NeTEx format.\n";

    /**
     * Progress bar
     *
     * @var Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar = null;

    /**
     * Import statistics.
     */
    protected $processedStops = 0;
    protected $processedGroups = 0;
    protected $processedPlaces = 0;
    protected $updatedStops = 0;
    protected $updatedGroups = 0;
    protected $updatedPlaces = 0;
    protected $createdStops = 0;
    protected $createdGroups = 0;
    protected $createdPlaces = 0;

    /**
     * Collections of items not seen during import, keyed by model name.
     *
     * @var []
     */
    protected $unseen = [];

    /**
     * Array of all elements/models that has id, version, changed and updated
     * attributes.
     */
    protected $models = ['StopPlace', 'StopQuay', 'GroupOfStopPlaces', 'TopographicPlace'];

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
        $xmlFile = $this->argument('xml');
        $this->logInfo("netex:importstops: BEGIN: %s", $xmlFile);
        $reader = new ChristmasTreeParser();
        if (!$reader->open($xmlFile)) {
            $this->error("Could not open XML file for reading: '$xmlFile'");
        }
        $this->info("Importing ...");
        $this->unseen['StopPlace'] = DB::table('netex_stop_place')->pluck('id', 'id');
        $this->unseen['StopQuay'] = DB::table('netex_stop_quay')->pluck('id', 'id');
        $this->unseen['GroupOfStopPlaces'] = DB::table('netex_group_of_stop_places')->pluck('id', 'id');
        $this->unseen['TopographicPlace'] = DB::table('netex_topographic_place')->pluck('id', 'id');
        $this->progressBar = $this->output->createProgressBar(array_reduce(
            ['StopPlace', 'GroupOfStopPlaces', 'TopographicPlace'],
            function ($carry, $item) {
                return $carry +  $this->unseen[$item]->count();
            }
        ));
        $this->progressBar->start();
        $reader->addCallback(['SiteFrame', 'stopPlaces', 'StopPlace'], [$this, 'readStopPlace'])
            ->addCallback(['SiteFrame', 'groupsOfStopPlaces', 'GroupOfStopPlaces'], [$this, 'readGroupOfStopPlaces'])
            ->addCallback(['SiteFrame', 'topographicPlaces', 'TopographicPlace'], [$this, 'readTopographicPlace'])
            ->parse()
            ->close();
        $this->progressBar->finish();
        // Needed to terminate progress bar.
        $this->info("");
        if (!$this->option('keep')) {
            $this->deleteOld();
        }
        $this->importSummary();
    }

    /**
     * Callback handler for SiteFrame//stopPlaces//StopPlace
     */
    public function readStopPlace(ChristmasTreeParser $reader)
    {
        // First, find id, to see if it should be created or updated.
        $this->processedStops++;
        $this->progressBar->advance();
        $stopXml = $reader->expandSimpleXml();
        // We load quays first to allow our seen/unseen mechanism actually see
        // the quays in the XML before leaving parsing of given stop place.
        $this->readQuays($stopXml);
        if (!($stop = $this->prepareNetexModel($stopXml, 'StopPlace'))) {
            return;
        }
        $stop->name = $stopXml->Name;
        $stop->stopPlaceType = $stopXml->StopPlaceType;
        $stop->latitude = $stopXml->Centroid->Location->Latitude;
        $stop->longitude = $stopXml->Centroid->Location->Longitude;
        $stop->validFromDate = $stopXml->ValidBetween->FromDate;
        $stop->validToDate = $stopXml->ValidBetween->ToDate;
        $stop->topographicPlaceRef = $stopXml->TopographicPlaceRef['ref'];
        $stop->parentSiteRef = $stopXml->ParentSiteRef['ref'];

        self::nullifyObjectProps($stop, ['stopPlaceType', 'validFromDate', 'validToDate', 'topographicPlaceRef', 'parentSiteRef']);
        $stop->updated_at ? $this->updatedStops++ : $this->createdStops++;
        $stop->save();
        $this->readAlternativeIds($stopXml, $stop->id, 'stop_place');
    }

    /**
     * Populate database with quays for selected stop place.
     */
    protected function readQuays(SimpleXMLElement $stopXml)
    {
        $stopId = (string) $stopXml['id'];
        if (!$stopXml->quays->Quay) {
            $this->logDebug("Empty <quays> element for stop id %s (%s)", $stopId, $stopXml->Name);
            return;
        }

        foreach ($stopXml->quays->Quay as $quayXml) {
            if (!($quay = $this->prepareNetexModel($quayXml, 'StopQuay'))) {
                continue;
            }
            $quay->stop_place_id = $stopId;
            $quay->latitude = $quayXml->Centroid->Location->Latitude;
            $quay->longitude = $quayXml->Centroid->Location->Longitude;
            $quay->privateCode = $quayXml->PrivateCode;
            $quay->publicCode = $quayXml->PublicCode;
            self::nullifyObjectProps($quay, ['publicCode']);
            $quay->save();
            $this->readAlternativeIds($quayXml, $quay->id, 'stop_quay');
        }
    }

    /**
     * Parse and populate alternative IDs for given stop type (place or quay)
     *
     * @param SimpleXMLElement $xmlElement
     * @param string $parentId
     *   The real ID.
     * @param string $base
     *   Either 'stop_place' or 'stop_quay'.
     */
    protected function readAlternativeIds(SimpleXMLElement $xmlElement, $netexId, $base)
    {
        // Remove all previous pointers to this stop.
        DB::table("netex_{$base}_alt_id")->where("{$base}_id", $netexId)->delete();
        foreach ($xmlElement->keyList->KeyValue as $keyVal) {
            if (((string) $keyVal->Key) !== 'imported-id') {
                continue;
            }

            $ids = explode(',', $keyVal->Value);
            if (!$ids) {
                continue;
            }
            // Also, rid ourselves of pointers to other stop ids for the alternatives
            DB::table("netex_{$base}_alt_id")->whereIn('alt_id', $ids)->delete();
            DB::table("netex_{$base}_alt_id")->insert(array_map(function ($item) use ($netexId, $base) {
                return ["alt_id" => $item, "{$base}_id" => $netexId];
            }, $ids));
            break;
        }
    }

    /**
     * Callback handler for SiteFrame//groupsOfStopPlaces//GroupOfStopPlaces.
     */
    public function readGroupOfStopPlaces($reader)
    {
        $this->processedGroups++;
        $this->progressBar->advance();
        $groupXml = $reader->expandSimpleXml();
        if (!($group = $this->prepareNetexModel($groupXml, 'GroupOfStopPlaces'))) {
            return;
        }
        $group->name = $groupXml->Name;
        $group->latitude = $groupXml->Centroid->Location->Latitude;
        $group->longitude = $groupXml->Centroid->Location->Longitude;
        $group->updated_at ? $this->updatedGroups++ : $this->createdGroups++;
        $group->save();
        $this->readGroupStopPlaceMembers($groupXml, $group);
    }

    protected function readGroupStopPlaceMembers($groupXml, $group)
    {
        DB::table('netex_stop_place_group_member')->where('group_of_stop_places_id', $group->id)->delete();
        $memberships = [];
        foreach ($groupXml->members->StopPlaceRef as $stopPlaceRef) {
            $memberships[] = [
                'stop_place_id' => $stopPlaceRef['ref'],
                'group_of_stop_places_id' => $group->id,
            ];
        }
        DB::table('netex_stop_place_group_member')->insert($memberships);
    }

    /**
     * Callback handler for SiteFrame//topographicPlaces//TopographicPlace.
     */
    public function readTopographicPlace($reader)
    {
        $this->processedPlaces++;
        $this->progressBar->advance();
        $placeXml = $reader->expandSimpleXml();
        if (!($topo = $this->prepareNetexModel($placeXml, 'TopographicPlace'))) {
            return;
        }
        $topo->name = $placeXml->Descriptor->Name;
        $topo->isoCode = $placeXml->IsoCode;
        $topo->topographicPlaceType = $placeXml->TopographicPlaceType;
        $topo->parentTopographicPlaceRef = $placeXml->ParentTopographicPlaceRef['ref'];
        $topo->validFromDate = $placeXml->ValidBetween->FromDate;
        $topo->validToDate = $placeXml->ValidBetween->ToDate;

        self::nullifyObjectProps($topo, [
            'topographicPlaceType',
            'isoCode',
            'parentTopographicPlaceRef',
            'validFromDate',
            'validToDate'
        ]);
        $topo->updated_at ? $this->updatedPlaces++ : $this->createdPlaces++;
        $topo->save();
    }

    /**
     * Create or load model based on given XML element.
     *
     * @param SimpleXMLElement $xmlElement
     *   A NeTEx element with 'id', 'version', 'created' and 'changed'
     *   attributes.
     * @param string $modelName
     *   The model name used for this element.
     */
    protected function prepareNetexModel($xmlElement, string $modelName)
    {
        // First, find id, to see if it should be created or updated.
        $modelId = (string) $xmlElement['id'];
        if ($this->unseen[$modelName]->has($modelId)) {
            $this->unseen[$modelName]->pull($modelId);
        }

        // Next is whether to process it or not. Every time an NeTEx element has
        // been updated, a new version is added, so just we use this for
        // comparison.
        $version = $xmlElement['version'];
        $modelClass = 'TromsFylkestrafikk\\Netex\\Models\\' . $modelName;
        if (!($model = $modelClass::find($modelId))) {
            $model = new $modelClass();
            $model->id = $modelId;
        } elseif ($version == $model->version) {
            return null;
        }
        $model->created = $xmlElement['created'];
        $model->changed = $xmlElement['changed'];
        $model->version = $version;
        self::nullifyObjectProps($model, ['created', 'changed']);
        return $model;
    }

    protected static function nullifyObjectProps($obj, $nullables)
    {
        foreach ($nullables as $nullable) {
            if (!$obj->{$nullable}) {
                $obj->{$nullable} = null;
            }
        }
    }

    /**
     * Delete items that wasn't seen during import.
     */
    protected function deleteOld()
    {
        array_walk($this->models, function ($model) {
            $modelClass = "TromsFylkestrafikk\\Netex\\Models\\$model";
            $modelClass::destroy($this->unseen[$model]->toArray());
        });
        DB::table('netex_stop_place_alt_id')->whereIn('stop_place_id', $this->unseen['StopPlace']->toArray())->delete();
        DB::table('netex_stop_quay_alt_id')->whereIn('stop_quay_id', $this->unseen['StopQuay']->toArray())->delete();
    }

    protected function importSummary()
    {
        $this->info(sprintf(
            "TopographicPlace: %d processed, %d updated, %d created",
            $this->processedPlaces,
            $this->updatedPlaces,
            $this->createdPlaces
        ));
        $this->info(sprintf(
            "GroupOfStopPlaces: %d processed, %d updated, %d created",
            $this->processedGroups,
            $this->updatedGroups,
            $this->createdGroups
        ));
        $this->info(sprintf(
            "StopPlace: %d processed, %d updated, %d created",
            $this->processedStops,
            $this->updatedStops,
            $this->createdStops
        ));
        if (!$this->option('keep')) {
            $this->info(sprintf(
                "DELETIONS: StopPlace: %d, StopQuay: %d, GroupOfStopPlaces: %d, TopographicPlace: %d",
                $this->unseen['StopPlace']->count(),
                $this->unseen['StopQuay']->count(),
                $this->unseen['GroupOfStopPlaces']->count(),
                $this->unseen['TopographicPlace']->count(),
            ));
        }
        $this->logInfo(
            "netex:importstops: END. Imported %d stops. %d new, %d updated.",
            $this->processedStops,
            $this->createdStops,
            $this->updatedStops
        );
    }

    protected function logDebug(...$args)
    {
        Log::debug(call_user_func_array('sprintf', $args));
    }

    protected function logInfo(...$args)
    {
        Log::info(call_user_func_array('sprintf', $args));
    }
}
