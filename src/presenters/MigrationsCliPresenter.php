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
        $this->migrationsService->onMigration[] = function(\SplFileInfo $file, $action, $args) {
            if ($action === MigrationsService::STATUS_RUNNING)
                echo "Running file {$file->getFilename()}\n";

            else if ($action === MigrationsService::STATUS_COMPLETED)
                echo "File {$file->getFilename()} completed\n";

            else if ($action === MigrationsService::STATUS_FAILED)
                echo "File {$file->getFilename()} failed: {$args->getMessage()}\n";
        };

        try {
            $this->migrationsService->run();
            echo 'Migration completed successfully';

        } catch (\Exception $ex) {
            echo 'Migration failed';
            exit(255);
        }

        $this->terminate();
    }
}