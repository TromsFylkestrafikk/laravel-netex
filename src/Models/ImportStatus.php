<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ImportStatus extends Model
{
    protected $table = 'netex_import';
    public $timestamps = false;

    protected $attributes = [
        // Status.
        'isValid' => false,
        'isError' => false,
        'isSuccess' => false,
        'isIncomplete' => false,

        // File info.
        'name' => null,
        'md5' => null,

        // Import details.
        'import_date' => null,
        'valid_to' => null,
        'version' => 1,
    ];

    public function appendData($fileInfo, $date, $valid_to)
    {
        $this->insert([
            'name' => $fileInfo['name'],
            'size' => $fileInfo['size'],
            'md5' => $fileInfo['md5'],
            'import_date' => $date,
            'valid_to' => $valid_to,
            'version' => 1,
        ]);
        $this->isValid = true;
        $this->isIncomplete = true;
        $this->isError = false;
        $this->isSuccess = false;
    }

    public function getLatest()
    {
        $data = $this->latest('name')->first();
        if ($data) {
            $this->isValid = true;
            $this->isError = $data->status === 'ERROR!';
            $this->isSuccess = $data->status === 'Done';
            $this->isIncomplete = $data->status === null;
            $this->name = $data['name'];
            $this->md5 = $data['md5'];
            $this->import_date = $data['import_date'];
            $this->valid_to = $data['valid_to'];
            $this->version = $data['version'];
        }
        return $data;
    }

    public function setError()
    {
        $this->isError = true;
        $this->isSuccess = false;
        $this->isIncomplete = false;
        $name = $this->latest('name')->first()?->name;
        if ($name) {
            $this->where('name', $name)->update(['status' => 'ERROR!']);
        } else {
            Log::error('Unable to update status in netex_import table!');
        }
    }

    public function setSuccess()
    {
        $this->isSuccess = true;
        $this->isIncomplete = false;
        $this->isError = false;
        $name = $this->latest('name')->first()?->name;
        if ($name) {
            $this->where('name', $name)->update(['status' => 'Done']);
        } else {
            Log::error('Unable to update status in netex_import table!');
        }
    }
}
