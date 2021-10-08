<?php

namespace dnj\SSH\Native;

use dnj\Filesystem\Contracts\IFile;
use dnj\Filesystem\Local\File;
use dnj\SSH\Contracts\ISCP;
use Exception;

class SCP implements ISCP
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param File $local
     */
    public function upload(IFile $local, string $remote, int $createMode): self
    {
        if (!$local instanceof File) {
            throw new \InvalidArgumentException('Only local files are supported');
        }
        $result = ssh2_scp_send($this->connection->getResource(), $local->getPath(), $remote, $createMode);
        if (!$result) {
            throw new Exception('Cannot upload');
        }

        return $this;
    }

    /**
     * @param File $local
     */
    public function download(string $remote, IFile $local): self
    {
        if (!$local instanceof File) {
            throw new \InvalidArgumentException('Only local files are supported');
        }
        $result = ssh2_scp_recv($this->connection->getResource(), $remote, $local->getPath());
        if (!$result) {
            throw new Exception('Cannot download');
        }

        return $this;
    }
}
