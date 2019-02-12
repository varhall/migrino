<?php

namespace Varhall\Migrino\Presenters;

use Varhall\Migrino\MigrationsService;

trait MigrationsCliPresenter
{
    protected $migrationsService = null;

    public function injectMigrationsService(MigrationsService $service)
    {
        $this->migrationsService = $service;
    }

    public function renderRun()
    {
        $result = true;
        $this->migrationsService->onMigration[] = function(\SplFileInfo $file, $action, $args) use (&$result) {
            $result = $result || $action === MigrationsService::STATUS_FAILED;
        };

        $this->migrationsService->onMigration[] = function(\SplFileInfo $file, $action, $args) {
            if ($action === MigrationsService::STATUS_RUNNING)
                echo "Running migration {$file->getFilename()}\n";

            else if ($action === MigrationsService::STATUS_COMPLETED)
                echo "Migration {$file->getFilename()} completed successfully\n";

            else if ($action === MigrationsService::STATUS_FAILED)
                echo "Migration {$file->getFilename()} failed {$args->getMessage()}\n";
        };

        $this->migrationsService->run();

        return +!$result;   // negation converted to int
    }
}