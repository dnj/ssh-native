<?php

namespace dnj\SSH\Native;

use dnj\SSH\Contracts\FingerprintType;
use dnj\SSH\Contracts\IAuthentication;
use dnj\SSH\Contracts\IConnection;
use Exception;

class Connection implements IConnection
{
    protected string $host;
    protected int $port;

    /**
     * @var resource|null
     */
    protected $resource;

    protected ?SFTP $sftp = null;

    /**
     * @param resource $resource
     */
    public function __construct(string $host, int $port, $resource)
    {
        $this->host = $host;
        $this->port = $port;
        $this->resource = $resource;
    }

    public function __destruct()
    {
        // $this->close();
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        if (!$this->resource) {
            throw new Exception('Resource already closed');
        }

        return $this->resource;
    }

    public function getFingerprint(FingerprintType $format, bool $raw): string
    {
        $flags = $raw ? SSH2_FINGERPRINT_RAW : SSH2_FINGERPRINT_HEX;
        if ($format->equals(FingerprintType::MD5())) {
            $flags |= SSH2_FINGERPRINT_MD5;
        } elseif ($format->equals(FingerprintType::SHA1())) {
            $flags |= SSH2_FINGERPRINT_SHA1;
        } else {
            throw new Exception('unsupported format');
        }
        $result = ssh2_fingerprint($this->getResource(), $flags);

        if (false === $result) {
            throw new Exception('Failed to get fingerprint');
        }

        return $result;
    }

    /**
     * @return static
     */
    public function login(IAuthentication $authentication): self
    {
        $authentication->authenticate($this);

        return $this;
    }

    /**
     * @return static
     */
    public function loginByPassword(string $username, string $password): self
    {
        return $this->login(new PasswordAuthentication($username, $password));
    }

    public function execute(array $commandLine): Process
    {
        $commandLine = implode(' ', array_map([$this, 'escapeArgument'], $commandLine));
        $stdout = ssh2_exec($this->getResource(), $commandLine);
        if (false === $stdout) {
            throw new Exception('Failed to execute');
        }

        $stderr = ssh2_fetch_stream($stdout, SSH2_STREAM_STDERR);
        if (false === $stderr) {
            throw new Exception('Failed to execute');
        }

        return new Process($this, $stdout, $stderr);
    }

    public function getScp(): SCP
    {
        return new SCP($this);
    }

    public function getSftp(): SFTP
    {
        if (!$this->sftp) {
            $this->sftp = new SFTP($this);
        }

        return $this->sftp;
    }

    public function close(): void
    {
        if (!$this->resource) {
            return;
        }
        ssh2_disconnect($this->resource);
        $this->resource = null;
    }

    /**
     * Escapes a string to be used as a shell argument.
     */
    protected function escapeArgument(?string $argument): string
    {
        if ('' === $argument || null === $argument) {
            return '""';
        }
        if (str_contains($argument, "\0")) {
            $argument = str_replace("\0", '?', $argument);
        }
        if (!preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
            return $argument;
        }
        /**
         * @var string
         */
        $argument = preg_replace('/(\\\\+)$/', '$1$1', $argument);

        return '"'.str_replace(['"', '^', '%', '!', "\n"], ['""', '"^^"', '"^%"', '"^!"', '!LF!'], $argument).'"';
    }
}
