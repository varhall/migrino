<?php

namespace Varhall\Migrino\Storages;

/**
 * Migrations log storage interface
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
interface IStorage
{
    /**
     * Sets configuration to data storage provider
     *
     * @param array $configuration
     * @return void
     */
    public function setConfiguration(array $configuration);

    /**
     * Checks whether storage is ready (accessible, exists, ...)
     *
     * @return bool
     */
    public function checkStorage();

    /**
     * Returns list of passed migration files from data storage
     *
     * @return array
     */
    public function passed();

    /**
     * Adds succesfully migrated file to data storage
     *
     * @param $file
     * @return void
     */
    public function add($file);
}
