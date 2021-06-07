<?php

declare(strict_types=1);

namespace ShinyDeploy\Action;

use ShinyDeploy\Core\Action;

abstract class CliAction extends Action
{
    /**
     * Reads and returns user-input from terminal.
     *
     * @param string $prompt A message requesting the input.
     * @param bool $hidden If true user input will not be displayed.
     * @return string
     */
    protected function requestUserInput(string $prompt, bool $hidden = false): string
    {
        $this->out($prompt);

        if ($hidden === false) {
            $userInput = trim(fgets(STDIN));
        } else {
            $oldStyle = shell_exec('stty -g');
            shell_exec('stty -echo');
            $userInput = rtrim(fgets(STDIN), "\n");
            shell_exec('stty ' . $oldStyle);
            $this->lb();
        }

        return $userInput;
    }

    /**
     * Echos text to stdout.
     *
     * @param string $prompt
     * @return void
     */
    protected function out(string $prompt): void
    {
        fwrite(STDOUT, $prompt);
    }

    /**
     * Echos line to stdout.
     *
     * @param string $line
     * @return void
     */
    protected function line(string $line): void
    {
        $this->out($line);
        $this->lb();
    }

    /**
     * Echos an error-message to stdout.
     *
     * @param string $message
     * @return void
     */
    protected function error(string $message): void
    {
        $line = "\033[0;31m" . $message . "\033[0m";
        $this->line($line);
    }

    /**
     * Echos a success message to stdout.
     *
     * @param string $message
     * @return void
     */
    protected function success(string $message): void
    {
        $line = "\033[0;32m" . $message . "\033[0m";
        $this->line($line);
    }

    /**
     * Echos an info message to stdout.
     *
     * @param string $message
     * @return void
     */
    protected function info(string $message): void
    {
        $line = "\033[0;33m" . $message . "\033[0m";
        $this->line($line);
    }

    /**
     * Echos linebreaks to stdout.
     *
     * @param int $count Number of linebreaks to send.
     * @return void
     */
    protected function lb(int $count = 1): void
    {
        fwrite(STDOUT, str_repeat(PHP_EOL, $count));
    }
}
