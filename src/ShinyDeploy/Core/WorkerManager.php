<?php namespace ShinyDeploy\Core;

use Noodlehaus\Config;

/**
 * Class WorkerManager
 *
 * Handles worker control stuff like startup, restart, eg.
 *
 */
class WorkerManager
{
    /**@var string $gearmanHost */
    protected $gearmanHost;

    /** @var int $gearmanPort */
    protected $gearmanPort;

    /** @var string $workerPath  */
    protected $workerPath;

    /** @var string $logPath */
    protected $logPath;

    /** @var string $pidPath */
    protected $pidPath;

    /** @var array $startupConfig */
    protected $startupConfig = [];

    /** @var string $processIdentifier */
    protected $processIdentifier;

    /** @var int $timeTillGhost */
    protected $timeTillGhost;

    /** @var array $pids Holds worker process ids. */
    protected $pids = [];

    public function __construct(Config $config)
    {
        $this->gearmanHost = $config->get('gearman.host');
        $this->gearmanPort = $config->get('gearman.port');
        $this->workerPath = $config->get('gearman.workerPath');
        $this->logPath = $config->get('gearman.logPath');
        $this->pidPath = $config->get('gearman.pidPath');
        $this->startupConfig = $config->get('gearman.workerScripts');
        $this->processIdentifier = $config->get(('gearman.processIdentifier'));
        $this->timeTillGhost = $config->get(('gearman.timeTillGhost'));

        $this->loadPids();
    }

    /**
     * Startup workers.
     *
     * @param string $typeFilter If given only workers of this type will be started.
     * @return bool
     */
    public function start($typeFilter = '')
    {
        foreach ($this->startupConfig as $workerType => $workerConfig) {
            // don't start workers of different type if filter is set
            if (!empty($typeFilter) && $typeFilter !== $workerType) {
                continue;
            }

            // don't start new workers if already running:
            if (!empty($this->pids[$workerType])) {
                continue;
            }

            // startup the workers:
            for ($i = 0; $i < $workerConfig['instances']; $i++) {
                $this->startupWorker($workerType);
            }
        }
        return true;
    }

    /**
     * Stop workers.
     *
     * @param string $typeFilter If given only workers of this type will be started.
     * @return bool
     */
    public function stop($typeFilter = '')
    {
        foreach ($this->startupConfig as $workerType => $workerConfig) {
            // don't stop workers of different type if filter is set
            if (!empty($typeFilter) && $typeFilter !== $workerType) {
                continue;
            }

            // skip if no worker running:
            if (empty($this->pids[$workerType])) {
                continue;
            }

            // stop the workers:
            foreach ($this->pids[$workerType] as $pid) {
                exec(escapeshellcmd('kill ' . $pid));
            }
        }
        $this->reloadPids();
        return true;
    }

    /**
     * Restart workers.
     *
     * @param string $workerId If given only workers of this type will be started.
     * @return bool
     */
    public function restart($workerId = '')
    {
        $this->stop($workerId);
        sleep(2);
        $this->start($workerId);
        return true;
    }

    /**
     * Pings every worker and displays result.
     *
     * @return array Status information.
     */
    public function status()
    {
        $status = [];
        if (empty($this->pids)) {
            return $status;
        }

        $Client = new \GearmanClient();
        $Client->addServer($this->gearmanHost, $this->gearmanPort);
        $Client->setTimeout(1000);
        foreach ($this->pids as $workerPids) {
            foreach ($workerPids as $workerName => $workerPid) {
                // raises php warning on timeout so we need the "evil @" here...
                $status[$workerName] = false;
                $start = microtime(true);
                $pong = @$Client->doHigh('ping_'.$workerName, 'ping');
                if ($pong === 'pong') {
                    $jobinfo = @$Client->doHigh('jobinfo_'.$workerName, 'foo');
                    $jobinfo = json_decode($jobinfo, true);
                    $status[$workerName] = $jobinfo;
                    $pingtime = microtime(true) - $start;
                    $status[$workerName]['ping'] = $pingtime;
                }
            }
        }

        return $status;
    }

    /**
     * Pings every worker and does a "restart" if worker is not responding.
     *
     * @return bool
     */
    public function keepalive()
    {
        // if already running don't do anything:
        if ($this->managerIsRunning() === true) {
            return false;
        }

        // if there are no workers at all do a fresh start:
        if (empty($this->pids)) {
            return $this->start();
        }

        // update pid-files of all workers:
        $this->updatePidFiles();

        // kill not responding workers:
        $this->killGhosts();

        // startup new workers if necessary:
        $this->adjustRunningWorkers();

        // delete old pid files:
        $this->pidCleanup();

        return true;
    }

    /**
     * Starts a new worker.
     *
     * @param $workerType
     */
    protected function startupWorker($workerType)
    {
        $workerFilename = $this->startupConfig[$workerType]['filename'];
        $workerId = $this->getId();
        $startupCmd = 'php ' . $this->workerPath . $workerFilename . ' --name ' . $workerType . '_' . $workerId;
        exec(escapeshellcmd($startupCmd) . ' >> ' . $this->logPath . $workerType.'.log 2>&1 &');
        $this->reloadPids();
    }

    /**
     * Updates timestamp in workers pid files.
     *
     * @return bool
     */
    protected function updatePidFiles()
    {
        if (empty($this->pids)) {
            return false;
        }

        $Client = new \GearmanClient();
        $Client->addServer($this->gearmanHost, $this->gearmanPort);
        $Client->setTimeout(1000);
        foreach ($this->pids as $workerPids) {
            foreach ($workerPids as $workerName => $workerPid) {
                if (method_exists($Client, 'doHigh')) {
                    @$Client->doHigh('pidupdate_'.$workerName, 'shiny');
                } else {
                    @$Client->do('pidupdate_'.$workerName, 'shiny');
                }
            }
        }
        return true;
    }

    /**
     * Kills workers which did not update there PID file for a while.
     *
     * @return bool
     */
    protected function killGhosts()
    {
        foreach ($this->pids as $workerPids) {
            foreach ($workerPids as $workerName => $workerPid) {
                $pidFile = $this->pidPath . $workerName . '.pid';
                if (!file_exists($pidFile)) {
                    throw new \RuntimeException('PID file not found.');
                }
                $lastActivity = file_get_contents($pidFile);
                $timeInactive = time() - (int)$lastActivity;
                if ($timeInactive < $this->timeTillGhost) {
                    continue;
                }
                exec(escapeshellcmd('kill ' . $workerPid));
            }
        }
        $this->reloadPids();
        return true;
    }

    /**
     * Starts up new workers if there are currently running less workers than required.
     *
     * @return bool
     */
    protected function adjustRunningWorkers()
    {
        foreach ($this->startupConfig as $workerType => $workerConfig) {
            $workersActive = count($this->pids[$workerType]);
            $workersTarget = (int)$workerConfig['instances'];
            if ($workersActive >= $workersTarget) {
                continue;
            }

            $workerDiff = $workersTarget - $workersActive;
            for ($i = 0; $i < $workerDiff; $i++) {
                $this->startupWorker($workerType);
            }
        }
        return true;
    }

    /**
     * Gets the process-ids for all workers.
     *
     * @return bool
     */
    protected function loadPids()
    {
        $cliOutput = [];
        exec('ps x | grep ' . $this->processIdentifier, $cliOutput);
        foreach ($cliOutput as $line) {
            $line = trim($line);
            $procInfo = preg_split('#\s+#', $line);
            $pid = $procInfo[0];
            $command = $procInfo[5];
            $workerName = (!empty($procInfo[7])) ? $procInfo[7] : null;
            foreach ($this->startupConfig as $workerType => $workerConfig) {
                if (strpos($command, $workerConfig['filename']) !== false) {
                    $this->pids[$workerType][$workerName] = $pid;
                }
            }
        }
        return true;
    }

    /**
     * Reloads the process ids (e.g. during restart)
     *
     * @return bool
     */
    protected function reloadPids()
    {
        $this->pids = [];
        return $this->loadPids();
    }

    /**
     * Checks if an instance of worker manager is already running.
     *
     * @return bool
     */
    protected function managerIsRunning()
    {
        global $argv;
        $cliOutput = array();
        exec('ps x | grep ' . $argv[0], $cliOutput);
        $processCount = 0;
        if (empty($cliOutput)) {
            return false;
        }
        foreach ($cliOutput as $line) {
            if (strpos($line, 'grep') !== false) {
                continue;
            }
            if (strpos($line, '/bin/sh') !== false) {
                continue;
            }
            $processCount++;
        }
        return ($processCount > 1) ? true : false;
    }

    /**
     * Deletes old PID files.
     */
    protected function pidCleanup()
    {
        $pidFiles = glob($this->pidPath . '*.pid');
        if (empty($pidFiles)) {
            return true;
        }
        $activeWorkerNames = [];
        foreach ($this->pids as $workerType => $typePids) {
            $activeWorkerNames = array_merge($activeWorkerNames, array_keys($typePids));
        }
        foreach ($pidFiles as $pidFilePath) {
            $filename = basename($pidFilePath, '.pid');
            if (!in_array($filename, $activeWorkerNames)) {
                unlink($pidFilePath);
            }
        }
        return true;
    }

    /**
     * Genrates a random string of given length.
     *
     * @param int $length
     * @return string
     */
    protected function getId($length = 6)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }
}
