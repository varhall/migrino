<?php

namespace Varhall\Migrino;

use Nette\Database\Context;
use Nette\InvalidStateException;
use Varhall\Migrino\Storages\DatabaseStorage;
use Varhall\Migrino\Storages\FileStorage;
use Varhall\Migrino\Storages\IStorage;

/**
 * Migrations management service
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class MigrationsService
{
    const STORAGE_FILE          = 'file';
    const STORAGE_DATABASE      = 'database';
    
    const SOURCE_MIGRATIONS     = 'migrations';
    const SOURCE_SEEDS          = 'seeds';

    protected $database         = NULL;

    protected $configuration    = NULL;

    protected $storages         = [];

    public function __construct(Context $database)
    {
        $this->database = $database;

        $this->storages = [
            self::STORAGE_FILE      => new FileStorage(),
            self::STORAGE_DATABASE  => new DatabaseStorage()
        ];
    }
    
    ////// PUBLIC METHODS

    // CONFIG

    public function getStorage()
    {
        return isset($this->configuration['storage']) ? $this->configuration['storage'] : self::STORAGE_FILE;
    }

    public function setStorage($storage)
    {
        $this->configuration['storage'] = $storage;
    }

    public function getSource()
    {
        return $this->configuration['source'];
    }

    public function setSource($source)
    {
        $this->configuration['source'] = $source;
    }

    public function getStorageName()
    {
        return $this->configuration['storage_name'];
    }

    public function setStorageName($storageName)
    {
        $this->configuration['storage_name'] = $storageName;
    }


    /**
     * Returns list of passed migration files
     *
     * @return array
     */
    public function findPassed()
    {
        return $this->currentStorage()->passed();
    }

    /**
     * Returns list of files which has not been run yet
     *
     * @return array
     */
    public function findNew()
    {
        $result = [];
        
        $passed = $this->findPassed();
        foreach (\Nette\Utils\Finder::findFiles('*.sql')->from($this->sourceDir(self::SOURCE_MIGRATIONS)) as $file) {
            $found = FALSE;
            
            foreach ($passed as $item) {
                if ($item->name === $file->getFilename()) {
                    $found = TRUE;
                    break;
                }
            }
            
            if (!$found)
                $result[] = $file;
        }
        
        usort($result, function($a, $b) { return $a->getFileName() > $b->getFileName(); });
        
        return $result;
    }

    /**
     * Runs database migrations
     *
     * @throws \Exception
     */
    public function run()
    {
        foreach ($this->findNew() as $file) {
            $this->runFile($file);
        }
    }

    /**
     * Runs database seeding
     *
     * @throws \Exception
     */
    public function seed()
    {
        foreach (\Nette\Utils\Finder::findFiles('*.sql')->from($this->sourceDir(self::SOURCE_SEEDS)) as $file) {
            $this->runFile($file);
        }
    }

    // PROTECTED & PRIVATE METHODS

    /**
     * Runs a SQL file in transaction. If SQL command fails, transaction is rolled back.
     *
     * @param \SplFileInfo $file
     * @throws \Exception
     */
    protected function runFile(\SplFileInfo $file)
    {
        try {
            $sql = file_get_contents($file->getPathname());

            if (empty($sql))
                return;

            $this->database->getConnection()->beginTransaction();

            $this->database->getConnection()->query($sql);
            $this->currentStorage()->add($file->getFilename());

            $this->database->getConnection()->commit();

        } catch (\Exception $ex) {
            $this->database->getConnection()->rollBack();
            throw $ex;
        }
    }

    /**
     * Source directory where the SQL files are loaded from
     *
     * @param $type
     * @return string
     */
    protected function sourceDir($type)
    {
        return $this->getSource() . DIRECTORY_SEPARATOR . $type;
    }

    /**
     * Gets active migration storage
     *
     * @return IStorage
     */
    protected function currentStorage()
    {
        $name = $this->getStorage();

        if (!isset($this->storages[$name]))
            throw new \Nette\InvalidStateException("Storage '{$name}' does not exist");

        $this->storages[$name]->setConfiguration($this->configuration);

        if (!$this->storages[$name]->checkStorage())
            throw new InvalidStateException("Storage '{$name}' is not available");

        return $this->storages[$name];
    }
}
