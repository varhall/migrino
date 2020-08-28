<?php

namespace Varhall\Migrino\Models;


abstract class Migration
{
    /**
     * @var \Nette\DI\Container
     */
    public $container = null;

    /**
     * @var \Nette\Database\Context
     */
    public $context = null;


    public function up()
    {

    }

    public function down()
    {

    }
}