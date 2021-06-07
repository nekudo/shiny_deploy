<?php namespace ShinyDeploy\Core;

use Noodlehaus\Config;

class WssManager
{
    /** @var string $wssPath  */
    protected string $wssPath;

    /** @var string $logPath */
    protected string $logPath;

    /** @var string $processIdentifier */
    protected string $processIdentifier;

    /** @var int $pid Holds server process ids. */
    protected int $pid;

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
    public function start(): bool
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
    public function stop(): bool
    {
        if (empty($this->pid)) {
            return true;
        }
        exec(escapeshellcmd('kill ' . $this->pid));
        $this->reloadPid();
        return true;
    }

    /**
     * Restart websocket server.
     *
     * @return bool
     */
    public function restart(): bool
    {
        $this->stop();
        sleep(2);
        return $this->start();
    }

    /**
     * Checks if websocket server is running.
     *
     * @return bool
     */
    public function status(): bool
    {
        $this->reloadPid();
        return ($this->pid > 0);
    }

    /**
     * Check if WSS is running and restarts if necessary.
     *
     * @return bool
     */
    public function keepalive(): bool
    {
        // if there are no workers at all do a fresh start:
        if (empty($this->pid)) {
            return $this->start();
        }
        return true;
    }

    /**
     * Estimates PID of websocket-server process.
     *
     * @return bool
     */
    protected function loadPid(): bool
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
            $this->pid =  (int)$pid;
        }
        return true;
    }

    /**
     * Reloads the process id of the websocket-server.
     *
     * @return bool
     */
    protected function reloadPid(): bool
    {
        $this->pid = 0;
        return $this->loadPid();
    }
}
