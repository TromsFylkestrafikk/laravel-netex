<?php

namespace TromsFylkestrafikk\Netex\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\Import;

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
     * Path to route set, relative to configured disk
     *
     * @var string
     */
    protected $path;

    /**
     * Name of XML file with shared data.
     *
     * @var string
     */
    protected $sharedFile;

    /**
     * Calculated md5 sum of entire set.
     *
     * @var string|null
     */
    protected $md5 = null;

    /**
     * List of XML files in set.
     *
     * @var string[]|null
     */
    protected $files = null;

    /**
     * @var int|null
     */
    protected $size = null;

    /**
     * @param string $path Path relative to configured disk.
     * @param string $sharedFile Name of xml filed with shared data across set.
     * @param string $id Name/id of set. Generated if not given.
     */
    public function __construct(string $path, string $sharedFile, $id = null)
    {
        $this->id = $id;
        $this->sharedFile = $sharedFile;
        $this->path = $path;
        $fullPath = $this->getFullPath();
        if (!is_dir($fullPath)) {
            throw new Exception("Directory does not exist: " . $fullPath);
        }
        if (!file_exists($this->getFilePath($sharedFile))) {
            throw new Exception("Shared data file not found within set: " . $sharedFile);
        }
        if (!count($this->getFiles())) {
            throw new Exception("Empty folder in route set: " . $path);
        }
    }

    /**
     * True if this set differ from existing, latest import.
     *
     * @return bool
     */
    public function isModified(): bool
    {
        $lastImport = Import::latest()->first();
        if (!$lastImport) {
            return true;
        }
        if ($lastImport->import_status !== 'imported') {
            return true;
        }
        return $lastImport->md5 !== $this->getMd5();
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
        $root = config(sprintf("filesystems.%s.root", config('netex.disk')));
        $md5s = [];
        foreach ($this->getFiles() as $xmlFile) {
            $md5s[] = md5_file("$root/$xmlFile");
        }
        $this->md5 = md5(implode(':', $md5s));
        return $this->md5;
    }

    /**
     * Get path to route set, relative to netex disk.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get full file system path to route set.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        $root = config(sprintf("filesystems.disks.%s.root", config('netex.disk')));
        return "$root/{$this->path}";
    }

    /**
     * Get name of shared XML data file.
     *
     * @return string
     */
    public function getSharedFile(): string
    {
        return $this->sharedFile;
    }

    /**
     * Get full path to shared XML file in set.
     *
     * @return string
     */
    public function getSharedFilePath(): string
    {
        return $this->getFilePath($this->getSharedFile());
    }

    /**
     * Get list of XML files in Route set with full file system path.
     *
     * @return string[]
     */
    public function getFiles(): array
    {
        if ($this->files === null) {
            $this->files = glob(sprintf("%s/*.xml", $this->getFullPath()));
            sort($this->files);
        }
        return $this->files;
    }

    /**
     * Get full path of given filename
     *
     * @param string $fileName
     * @return string
     */
    public function getFilePath($fileName): string
    {
        return sprintf("%s/%s", $this->getFullPath(), $fileName);
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
}
