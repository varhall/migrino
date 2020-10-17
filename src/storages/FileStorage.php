<?php

namespace Varhall\Migrino\Storages;

/**
 * Description of FileStorage
 *
 * @author sibrava
 */
class FileStorage implements IStorage
{
    protected $configuration = [];

    /// INTERFACE METHODS

    /**
     * Sets configuration to data storage provider
     *
     * @param array $configuration
     * @return void
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Checks whether storage is ready (accessible, exists, ...)
     *
     * @return bool
     */
    public function checkStorage()
    {
        if (file_exists($this->storageFileName()))
            return is_readable($this->storageFileName()) && is_writable($this->storageFileName());

        return is_writable(dirname($this->storageFileName()));
    }

    /**
     * Adds succesfully migrated file to data storage
     *
     * @param $file
     * @return void
     */
    public function add($file)
    {
        $data = $this->readStorageFile();
        $data[] = [
            'name'          => $file,
            'created_at'    => date('c')
        ];

        file_put_contents($this->storageFileName(), \Nette\Utils\Json::encode($data));
    }

    /**
     * Returns list of passed migration files from data storage
     *
     * @return array
     */
    public function passed()
    {
        $data = $this->readStorageFile();
        
        foreach ($data as &$item) {
            $item['created_at'] = \Nette\Utils\DateTime::createFromFormat('c', $item['created_at']);
        }
        
        return array_map(function($item) { return (object) $item; }, $data);
    }
    
    
    /// PROTECTED METHODS

    /**
     * Name of file where the migrations log is stored
     *
     * @return string
     */
    protected function storageFileName()
    {
        return $this->configuration['sourcedir'] . DIRECTORY_SEPARATOR . $this->configuration['storage_filename'] . '.json';
    }

    /**
     * Reads migrations log file. If file does not exist or file is not readable, empty array is returned.
     *
     * @return array
     */
    protected function readStorageFile()
    {
        if (!file_exists($this->storageFileName()))
            return [];
            
        try {
            $data = file_get_contents($this->storageFileName());
            return \Nette\Utils\Json::decode($data, \Nette\Utils\Json::FORCE_ARRAY);
        
        } catch (\Exception $ex) {
            return [];
        }
    }
}
