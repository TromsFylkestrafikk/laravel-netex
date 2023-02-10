<?php

namespace TromsFylkestrafikk\Netex\Services;

use TromsFylkestrafikk\Netex\Models\ImportStatus;

class RouteImportStatus
{
    // Status.
    public $isValid = false;
    public $isError = false;
    public $isSuccess = false;
    public $isIncomplete = false;

    // File info.
    public $name = null;
    public $size = 0;
    public $md5 = null;

    // Import details.
    public $import_date = null;
    public $valid_to = null;
    public $version = 0;

    /**
     * Create a new class interface.
     *
     * @return void
     */
    public function __construct()
    {
        $this->getLatest();
    }

    public function appendData($fileInfo, $date, $valid_to)
    {
        ImportStatus::create([
            'name' => $fileInfo['name'],
            'size' => $fileInfo['size'],
            'md5' => $fileInfo['md5'],
            'import_date' => $date,
            'valid_to' => $valid_to,
            'status' => null,
            'version' => 1,
        ]);
        $this->getLatest();
    }

    public function setSuccess()
    {
        $latest = $this->getLatest();
        if ($latest) {
            $latest->status = 'Done';
            $latest->save();
            $this->isSuccess = true;
            $this->isError = false;
            $this->isIncomplete = false;
        }
    }

    public function setError()
    {
        $latest = $this->getLatest();
        if ($latest) {
            $latest->status = 'ERROR!';
            $latest->save();
            $this->isError = true;
            $this->isSuccess = false;
            $this->isIncomplete = false;
        }
    }

    public function refresh()
    {
        $this->getLatest();
    }

    protected function getLatest()
    {
        $data = ImportStatus::latest('name')->first();
        if ($data) {
            $this->isValid = true;
            $this->isError = $data->status === 'ERROR!';
            $this->isSuccess = $data->status === 'Done';
            $this->isIncomplete = $data->status === null;
            $this->name = $data->name;
            $this->size = $data->size;
            $this->md5 = $data->md5;
            $this->import_date = $data->import_date;
            $this->valid_to = $data->valid_to;
            $this->version = $data->version;
        }
        return $data;
    }
}
