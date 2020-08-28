<?php

namespace Varhall\Migrino\Storages;

use Varhall\Migrino\Models\DatabaseMigration;

/**
 * Database migrations log storage
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class DatabaseStorage implements IStorage
{
    /**
     * Sets configuration to data storage provider
     *
     * @param array $configuration
     * @return void
     */
    public function setConfiguration(array $configuration)
    {

    }

    /**
     * Checks whether storage is ready (accessible, exists, ...)
     *
     * @return bool
     */
    public function checkStorage()
    {
        return TRUE;
    }

    /**
     * Returns list of passed migration files from data storage
     *
     * @return array
     */
    public function passed()
    {
        return DatabaseMigration::all();
    }

    /**
     * Adds succesfully migrated file to data storage
     *
     * @param $file
     * @return void
     */
    public function add($file)
    {
        DatabaseMigration::create([
            'name'  => $file
        ]);
    }
}