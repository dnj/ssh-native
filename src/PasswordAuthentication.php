<?php

namespace dnj\SSH\Native;

use dnj\SSH\Contracts\IConnection;
use dnj\SSH\Contracts\IPasswordAuthentication;
use Exception;

class PasswordAuthentication implements IPasswordAuthentication
{
    protected string $username;
    protected string $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function authenticate(IConnection $connection): self
    {
        if (!$connection instanceof Connection) {
            throw new Exception('Connection is unsupported');
        }
        $result = ssh2_auth_password($connection->getResource(), $this->username, $this->password);
        if (!$result) {
            throw new Exception('authentication failed');
        }

        return $this;
    }
}
