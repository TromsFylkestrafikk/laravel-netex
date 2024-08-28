<?php

namespace TromsFylkestrafikk\Netex\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Tools related to a full NeTEx route set in XML.
 */
class RouteSet
{
    /**
     * ID of route set.
     *
     * @var string
     */
    protected $id;

    /**
     * Full path to route set.
     *
     * @var string
     */
    protected $path;

    /**
     * Calculated md5 sum of entire set.
     *
     * @var string|null
     */
    protected $md5 = null;

    /**
     * @var string[]|null
     */
    protected $sharedFiles = null;

    /**
     * @var string[]|null
     */
    protected $lineFiles = null;

    /**
     * @var int|null
     */
    protected $size = null;

    /**
     * @param string $path Full path to directory with route set
     * @param string $id Name/id of set. Generated if not given
     *
     * @return void
     */
    public function __construct(string $path, $id = null)
    {
        $this->id = $id;
        $this->path = $path;
        if (!is_dir($path)) {
            throw new Exception("Directory does not exist: " . $path);
        }
        if (!count($this->getFiles())) {
            throw new Exception("Empty folder in route set: " . $path);
        }
    }

    /**
     * Get full path to route set.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get list of all XML files belonging to this set.
     *
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->getSharedFiles() + $this->getLineFiles();
    }

    /**
     * Get XML files with shared data
     *
     * @return string[]
     */
    public function getSharedFiles(): array
    {
        if ($this->sharedFiles === null) {
            $this->sharedFiles = glob(sprintf("%s/_*.xml", $this->getPath()));
            sort($this->sharedFiles);
        }
        return $this->sharedFiles;
    }

    /**
     * Get XML files with line data
     *
     * @return string[]
     */
    public function getLineFiles(): array
    {
        if ($this->lineFiles === null) {
            $this->lineFiles = glob(sprintf("%s/[!_]*.xml", $this->getPath()));
            sort($this->lineFiles);
        }
        return $this->lineFiles;
    }

    /**
     * Size of XML files.
     */
    public function getSize(): int
    {
        if ($this->size === null) {
            $this->size = array_reduce($this->getFiles(), function ($size, $file) {
                return $size + filesize($file);
            }, 0);
        }
        return $this->size;
    }

    /**
     * Generate md5 of route set.
     *
     * @return string
     */
    public function getMd5(): string
    {
        if ($this->md5 !== null) {
            return $this->md5;
        }
        $md5s = [];
        foreach ($this->getFiles() as $xmlFile) {
            $md5s[] = md5_file($xmlFile);
        }
        $this->md5 = md5(implode(':', $md5s));
        return $this->md5;
    }
}
