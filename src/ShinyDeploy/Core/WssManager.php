<?php namespace ShinyDeploy\Core;

use Noodlehaus\Config;

class WssManager
{
    /** @var string $wssPath  */
    protected $wssPath;

    /** @var string $logPath */
    protected $logPath;

    /** @var string $processIdentifier */
    protected $processIdentifier;

    /** @var int $pid Holds server process ids. */
    protected $pid;

    public function __construct(Config $config)
    {
        $this->wssPath = $config->get('wss.wssPath');
        $this->logPath = $config->get('wss.logPath');
        $this->processIdentifier = $config->get(('wss.processIdentifier'));

        $this->loadPid();
    }

    /**
     * Startup websocket server.
     *
     * @return bool
     */
    public function start()
    {
        if (!empty($this->pid)) {
            return true;
        }
        $startupCmd = 'php ' . $this->wssPath . 'wss.php';
        exec(escapeshellcmd($startupCmd) . ' >> ' . $this->logPath . 'wss.log 2>&1 &');
        $this->reloadPid();
        return true;
    }

    /**
     * Stop websocket server.
     *
     * @return bool
     */
    public function stop()
    {
        if (empty($this->pid)) {
            return true;
        }
        exec(escapeshellcmd('kill ' . $this->pid));
        $this->reloadPid();
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
        $this->stop();
        sleep(2);
        $this->start();
        return true;
    }

    /**
     * Checks if websocket server is running.
     *
     * @return bool
     */
    public function status()
    {
        $this->reloadPid();
        return ($this->pid > 0);
    }

    /**
     * Check if WSS is running and restarts if neccessary.
     *
     * @return bool
     */
    public function keepalive()
    {
        // if there are no workers at all do a fresh start:
        if (empty($this->pid)) {
            return $this->start();
        }
        return true;
    }

    /**
     * Gets the process-ids for all workers.
     *
     * @return bool
     */
    protected function loadPid()
    {
        $cliOutput = [];
        exec('ps x | grep ' . $this->processIdentifier, $cliOutput);
        foreach ($cliOutput as $line) {
            $line = trim($line);
            if (strpos($line, 'grep') !== false) {
                continue;
            }
            $procInfo = preg_split('#\s+#', $line);
            $pid = $procInfo[0];
            $command = $procInfo[5];
            $this->pid =  (int)$pid;
        }
        return true;
    }

    /**
     * Reloads the process ids (e.g. during restart)
     *
     * @return bool
     */
    protected function reloadPid()
    {
        $this->pid = 0;
        return $this->loadPid();
    }
}
