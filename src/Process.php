<?php

namespace dnj\SSH\Native;

use dnj\SSH\Contracts\IProcess;
use Exception;

class Process implements IProcess
{
    protected Connection $connection;

    /**
     * @var resource
     */
    protected $stdout;

    /**
     * @var resource
     */
    protected $stderr;

    protected ?string $output = null;
    protected ?string $error = null;

    /**
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(Connection $connection, $stdout, $stderr)
    {
        $this->connection = $connection;
        $this->stdout = $stdout;
        $this->stderr = $stderr;

        stream_set_blocking($this->stderr, true);
        stream_set_blocking($this->stdout, true);
        $output = stream_get_contents($this->stdout);
        if (false === $output) {
            $output = null;
        }
        $this->output = $output;

        $error = stream_get_contents($this->stderr);
        if (false === $error) {
            $error = null;
        }
        $this->error = $error;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getOutput(): string
    {
        if (null === $this->output) {
            throw new Exception('Cannot get stdout');
        }

        return $this->output;
    }

    public function getError(): string
    {
        if (null === $this->error) {
            throw new Exception('Cannot get stdout');
        }

        return $this->error;
    }
}
