<?php

namespace Varhall\Migrino\DI;

use Varhall\Migrino\MigrationsService;

/**
 * Nette extension class
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class MigrinoExtension extends \Nette\DI\CompilerExtension
{
    /**
     * Processes configuration data
     *
     * @return void
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $config = Helpers::merge($this->getConfig(), [
            'storage'       => MigrationsService::STORAGE_FILE,
            'storage_name'  => 'migrations',
            'source'        => $builder->parameters['wwwDir'] . DIRECTORY_SEPARATOR . 'sql'
        ]);

        $builder->addDefinition($this->prefix('migrino'))
            ->setFactory('Varhall\Migrino\MigrationsService')
            ->addSetup('setStorage', [ $config['storage'] ])
            ->addSetup('setStorageName', [ $config['storage_name'] ])
            ->addSetup('setSource', [ $config['source'] ]);
    }
}
