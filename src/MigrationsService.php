<?php

namespace Varhall\Migrino;

use Nette\Database\Context;
use Nette\DI\Container;
use Nette\InvalidStateException;
use Nette\SmartObject;
use Varhall\Migrino\Models\Migration;
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
    use SmartObject;

    public $onMigration;


    const STORAGE_FILE          = 'file';
    const STORAGE_DATABASE      = 'database';

    const SOURCE_MIGRATIONS     = 'migrations';
    const SOURCE_SEEDS          = 'seeds';

    const STATUS_RUNNING        = 'running';
    const STATUS_COMPLETED      = 'completed';
    const STATUS_FAILED         = 'failed';

    protected $container        = null;
    
    protected $database         = null;

    protected $configuration    = null;

    protected $storages         = [];

    public function __construct(Context $database, Container $container)
    {
        $this->database = $database;
        $this->container = $container;

        $this->storages = [
            self::STORAGE_FILE      => new FileStorage(),
            self::STORAGE_DATABASE  => new DatabaseStorage()
        ];
    }

    ////// PUBLIC METHODS

    // CONFIG

    public function getStorageType()
    {
        return isset($this->configuration['storage_type']) ? $this->configuration['storage_type'] : self::STORAGE_FILE;
    }

    public function setStorageType($type)
    {
        $this->configuration['storage_type'] = $type;
    }

    public function getSourcedir()
    {
        return $this->configuration['sourcedir'];
    }

    public function setSourcedir($source)
    {
        $this->configuration['sourcedir'] = $source;
    }

    public function getStorageFilename()
    {
        return $this->configuration['storage_filename'];
    }

    public function setStorageFilename($file)
    {
        $this->configuration['storage_filename'] = $file;
    }

    public function getNamespace()
    {
        return $this->configuration['namespace'];
    }

    public function setNamespace($namespace)
    {
        $this->configuration['namespace'] = $namespace;
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
        foreach (\Nette\Utils\Finder::findFiles('*.sql', '*.php')->from($this->sourceDir(self::SOURCE_MIGRATIONS)) as $file) {
            $found = FALSE;

            foreach ($passed as $item) {
                if ($item->name === $file->getFilename()) {
                    $found = true;
                    break;
                }
            }

            if (!$found)
                $result[] = $file;
        }

        usort($result, function($a, $b) { return -1 * ($a->getFileName() <=> $b->getFileName()); });

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

    /**
     * Force add file to passed files without running
     *
     * @param $file
     */
    public function skip($file)
    {
        $this->currentStorage()->add($file);
    }

    // PROTECTED & PRIVATE METHODS

    protected function runFile(\SplFileInfo $file)
    {
        if (preg_match('/\.sql$/i', $file->getFilename()))
            $this->runSQL($file);

        else if (preg_match('/\.php$/i', $file->getFilename()))
            $this->runPHP($file);
    }

    /**
     * Runs a SQL file in transaction. If SQL command fails, transaction is rolled back.
     *
     * @param \SplFileInfo $file
     * @throws \Exception
     */
    protected function runSQL(\SplFileInfo $file)
    {
        try {
            $this->onMigration($file, self::STATUS_RUNNING, null);

            $sql = file_get_contents($file->getPathname());

            if (empty($sql)) {
                $this->onMigration($file, self::STATUS_COMPLETED, null);
                return;
            }

            $this->database->getConnection()->beginTransaction();

            $this->database->getConnection()->query($sql);
            $this->currentStorage()->add($file->getFilename());

            $this->database->getConnection()->commit();
            $this->onMigration($file, self::STATUS_COMPLETED, null);

        } catch (\Exception $ex) {
            $this->database->getConnection()->rollBack();
            $this->onMigration($file, self::STATUS_FAILED, $ex);
            throw $ex;
        }
    }

    protected function runPHP(\SplFileInfo $file)
    {
        try {
            $this->onMigration($file, self::STATUS_RUNNING, null);

            include $file->getRealPath();

            $namespace = trim($this->getNamespace(), '\\');
            $classname = preg_replace('/\.php$/i', '', $file->getFilename());
            $classname = "\\{$namespace}\\{$classname}";

            $migration = new $classname();

            if (!($migration instanceof Migration))
                throw new \Nette\InvalidStateException("Class {$classname} is not instance of " . Migration::class);

            $migration->container = $this->container;
            $migration->context = $this->database;

            $migration->up();

            $this->currentStorage()->add($file->getFilename());
            $this->onMigration($file, self::STATUS_COMPLETED, null);

        } catch (\Exception $ex) {
            $this->onMigration($file, self::STATUS_FAILED, $ex);
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
        return $this->getSourcedir() . DIRECTORY_SEPARATOR . $type;
    }

    /**
     * Gets active migration storage
     *
     * @return IStorage
     */
    protected function currentStorage()
    {
        $name = $this->getStorageType();

        if (!isset($this->storages[$name]))
            throw new \Nette\InvalidStateException("Storage '{$name}' does not exist");

        $this->storages[$name]->setConfiguration($this->configuration);

        if (!$this->storages[$name]->checkStorage())
            throw new InvalidStateException("Storage '{$name}' is not available");

        return $this->storages[$name];
    }
}
