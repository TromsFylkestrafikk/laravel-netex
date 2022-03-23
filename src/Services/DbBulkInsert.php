<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DbBulkInsert
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $records;

    /**
     * @var int
     */
    protected $recordCount;

    /**
     * Number of buffered records before write.
     *
     * @var int
     */
    protected $bufferSize = 1000;

    public function __construct(string $table, $method = 'insert')
    {
        $this->table = $table;
        $this->method = $method;
        $this->resetBuffer();
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function addRecord($record)
    {
        $this->records[] = $record;
        $this->recordCount++;
        if ($this->recordCount >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function flush()
    {
        DB::table($this->table)->insert($this->records);
        $this->resetBuffer();
    }

    protected function resetBuffer()
    {
        $this->recordCount = 0;
        $this->records = [];
    }
}
