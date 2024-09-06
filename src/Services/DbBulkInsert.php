<?php

namespace TromsFylkestrafikk\Netex\Services;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

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
    protected $idColumns;

    /**
     * @var array
     */
    protected $uniqueCols;

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
     * Dummy run. Dont write to db.
     *
     * @var bool
     */
    protected $isDummy;

    /**
     * Number of buffered records before write.
     *
     * @var int
     */
    protected $bufferSize = 1000;

    public function __construct(string $table, $method = 'insert')
    {
        $allowedMethods = ['insert', 'insertOrIgnore', 'upsert'];
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException(sprintf(
                '%s: $method must be one of: %s',
                self::class,
                implode(', ', $allowedMethods)
            ));
        }
        $this->table = $table;
        $this->method = $method;
        $this->idColumns = [];
        $this->recordsWritten = 0;
        $this->isDummy = false;
        $this->resetBuffer();
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string[] $columns Columns to identify unique records.
     *
     * @return $this
     */
    public function unique(array $columns)
    {
        $this->uniqueCols = $columns;
        return $this;
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
        if (!$this->recordCount) {
            return $this;
        }
        if (! $this->isDummy) {
            if ($this->method === 'upsert') {
                DB::table($this->table)->upsert($this->records, $this->uniqueCols);
            } else {
                DB::table($this->table)->{$this->method}($this->records);
            }
        }
        $this->recordsWritten += $this->recordCount;
        $this->resetBuffer();
        return $this;
    }

    public function getRecordsWritten()
    {
        return $this->recordsWritten;
    }

    /**
     * Dummy run. Don't write anything to DB.
     */
    public function dummy(bool $isDummy = true): DbBulkInsert
    {
        $this->isDummy = $isDummy;
        return $this;
    }

    protected function resetBuffer()
    {
        $this->recordCount = 0;
        $this->records = [];
    }
}
