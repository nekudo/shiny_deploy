<?php

namespace ShinyDeploy\Domain\Updater;

use ShinyDeploy\Domain\Database\DatabaseDomain;

class Updater extends DatabaseDomain
{
    /**
     * Checks if PHP runs in cli mode.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function checkSapi() : void
    {
        if (php_sapi_name() !== 'cli') {
            throw new \RuntimeException('This script can only be executed in cli mode.');
        }
    }

    /**
     * Estimates tasks that need to be executed to update application to latest version.
     *
     * @return array
     */
    public function getTasks() : array
    {
        $taskFiles = glob(__DIR__ . '/Tasks/*.php');
        if (empty($taskFiles)) {
            return [];
        }
        $tasks = [];
        foreach ($taskFiles as $taskFile) {
            $className = '\ShinyDeploy\Domain\Updater\Tasks\\' . basename($taskFile, '.php');
            /** @var \ShinyDeploy\Core\UpdaterTask $task */
            $task = new $className($this->config, $this->logger, $this->db);
            if ($task->needsExecution() === true) {
                array_push($tasks, $task);
            }
        }

        return $tasks;
    }

    /**
     * Executes given tasks.
     *
     * @param array $tasks
     */
    public function executeTasks(array $tasks) : void
    {
        /** @var \ShinyDeploy\Core\UpdaterTask $task */
        foreach ($tasks as $task) {
            $task->__invoke();
        }
    }
}
