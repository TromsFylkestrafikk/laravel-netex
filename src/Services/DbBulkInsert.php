<?php

namespace TromsFylkestrafikk\Netex\Services;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Add DB records in bulk.
 *
 * In certain scenarios creating DB connections are costly, and frequent
 * DB::insert (e.g. in loops) may choke the performance.
 *
 * This tool can be used to buffer up db records connected to tables and write
 * them in bulk when buffer is full. The buffer is also flushed/written on
 * destruct, but adding a final $this->flush() is preferred.
 */
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
     * Total number of records written to db.
     *
     * @var int
     */
    protected $recordsWritten;

    /**
     * Number of buffered records before write.
     *
     * @var int
     */
    protected $bufferSize = 1000;

    public function __construct(string $table, $method = 'insert')
    {
        $allowedMethods = ['insert', 'insertOrIgnore'];
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException(sprintf(
                '%s: $method must be one of: %s',
                self::class,
                implode(', ', $allowedMethods)
            ));
        }
        $this->table = $table;
        $this->method = $method;
        $this->recordsWritten = 0;
        $this->resetBuffer();
    }

    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param array $record
     *
     * @return $this
     */
    public function addRecord($record)
    {
        $this->records[] = $record;
        $this->recordCount++;
        if ($this->recordCount >= $this->bufferSize) {
            $this->flush();
        }
        return $this;
    }

    /**
     * Write buffered records to DB.
     *
     * @return $this
     */
    public function flush()
    {
        DB::table($this->table)->{$this->method}($this->records);
        $this->recordsWritten += $this->recordCount;
        $this->resetBuffer();
        return $this;
    }

    public function getRecordsWritten()
    {
        return $this->recordsWritten;
    }

    protected function resetBuffer()
    {
        $this->recordCount = 0;
        $this->records = [];
    }
}
