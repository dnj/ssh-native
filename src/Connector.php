<?php

namespace dnj\SSH\Native;

use dnj\SSH\Contracts\IConnector;
use Exception;

class Connector implements IConnector
{
    public function __construct()
    {
        if (!extension_loaded('ssh2')) {
            throw new Exception("'ssh2' extension is required to use this connector");
        }
    }

    public function connect(string $host, int $port = 22): Connection
    {
        $resource = ssh2_connect($host, $port);
        if (false === $resource) {
            throw new Exception('Cannot connect to this host');
        }

        return new Connection($host, $port, $resource);
    }
}
