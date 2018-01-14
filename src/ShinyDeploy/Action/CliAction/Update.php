<?php

namespace ShinyDeploy\Action\CliAction;

use ShinyDeploy\Core\Action;
use ShinyDeploy\Domain\Updater\Updater;

class Update extends Action
{
    /**
     * @var Updater $updater
     */
    private $updater;

    /**
     * Fetches list of tasks that need to be executed to update the application and executes those tasks.
     *
     * @return void
     */
    public function __invoke() : void
    {
        try {
            $this->updater = new Updater($this->config, $this->logger);

            // Updater can only run in cli mode
            $this->updater->checkSapi();

            // Get tasks that need to be executed:
            $tasks = $this->updater->getTasks();
            if (empty($tasks)) {
                echo 'Nothing to migrate.' . PHP_EOL;
                return;
            }

            // Execute tasks:
            $this->updater->executeTasks($tasks);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
