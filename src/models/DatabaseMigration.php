<?php

namespace Varhall\Migrino\Models;

/**
 * Database migrations model class
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class DatabaseMigration extends \Varhall\Dbino\Model
{

    protected function plugins()
    {
        return [];
    }

    protected function softDeletes()
    {
        return FALSE;
    }

    protected function table()
    {
        return 'migrations';
    }
}