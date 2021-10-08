<?php

namespace dnj\SSH\Native;

use dnj\Filesystem\Contracts\IFile;
use dnj\Filesystem\Local\File;
use dnj\SSH\Contracts\ISFTP;
use Exception;

class SFTP implements ISFTP
{
    protected Connection $connection;

    /**
     * @var resource
     */
    protected $resource;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $resource = ssh2_sftp($connection->getResource());
        if (!$resource) {
            throw new Exception('Cannot open sftp from ssh');
        }
        $this->resource = $resource;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    public function getLink(string $path): string
    {
        return 'ssh2.sftp://'.intval($this->getResource()).'/'.ltrim($path, '/');
    }

    public function chmod(string $filename, int $mode): self
    {
        if (!ssh2_sftp_chmod($this->getResource(), $filename, $mode)) {
            throw new Exception();
        }

        return $this;
    }

    public function lstat(string $path): array
    {
        $result = ssh2_sftp_lstat($this->getResource(), $path);
        if (!$result) {
            throw new Exception();
        }

        return $result;
    }

    public function mkdir(string $dirname, int $mode = 0777, bool $recursive = false): self
    {
        if (!ssh2_sftp_mkdir($this->getResource(), $dirname, $mode, $recursive)) {
            throw new Exception();
        }

        return $this;
    }

    public function readlink(string $link): string
    {
        $result = ssh2_sftp_readlink($this->getResource(), $link);
        if (!$result) {
            throw new Exception();
        }

        return $result;
    }

    public function realpath(string $filename): string
    {
        $result = ssh2_sftp_readlink($this->getResource(), $filename);
        if (!$result) {
            throw new Exception();
        }

        return $result;
    }

    public function rename(string $from, string $to): self
    {
        if (!ssh2_sftp_rename($this->getResource(), $from, $to)) {
            throw new Exception();
        }

        return $this;
    }

    public function rmdir(string $dirname): self
    {
        if (!ssh2_sftp_rmdir($this->getResource(), $dirname)) {
            throw new Exception();
        }

        return $this;
    }

    public function stat(string $path): array
    {
        $result = ssh2_sftp_stat($this->getResource(), $path);
        if (!$result) {
            throw new Exception();
        }

        return $result;
    }

    public function symlink(string $target, string $link): self
    {
        if (!ssh2_sftp_symlink($this->getResource(), $target, $link)) {
            throw new Exception();
        }

        return $this;
    }

    public function unlink(string $filename): self
    {
        if (!ssh2_sftp_unlink($this->getResource(), $filename)) {
            throw new Exception();
        }

        return $this;
    }

    public function write(string $path, string $data, bool $append): self
    {
        if (false === file_put_contents($this->getLink($path), $data, $append ? FILE_APPEND : 0)) {
            throw new Exception();
        }

        return $this;
    }

    public function read(string $path, ?int $length = null): string
    {
        $result = file_get_contents($this->getLink($path), false, null, 0, $length);
        if (false === $result) {
            throw new Exception();
        }

        return $result;
    }

    public function size(string $path): int
    {
        $result = filesize($this->getLink($path));
        if (false === $result) {
            throw new Exception();
        }

        return $result;
    }

    public function md5(string $path, bool $raw): string
    {
        $result = md5_file($this->getLink($path), $raw);
        if (false === $result) {
            throw new Exception();
        }

        return $result;
    }

    public function sha1(string $path, bool $raw): string
    {
        $result = sha1_file($this->getLink($path), $raw);
        if (false === $result) {
            throw new Exception();
        }

        return $result;
    }

    public function isFile(string $path): bool
    {
        return is_file($this->getLink($path));
    }

    public function isDir(string $path): bool
    {
        return is_dir($this->getLink($path));
    }

    public function fileExists(string $path): bool
    {
        return file_exists($this->getLink($path));
    }

    public function touch(string $path, ?int $modifiedTime, ?int $accessTime): self
    {
        if (false === touch($this->getLink($path), $modifiedTime, $accessTime)) {
            throw new Exception();
        }

        return $this;
    }

    public function scanDir(string $path): array
    {
        $result = scandir($this->getLink($path));
        if (false === $result) {
            throw new Exception();
        }

        return $result;
    }

    /**
     * @param File $local
     */
    public function upload(IFile $local, string $remote): self
    {
        if (!$local instanceof File) {
            throw new \InvalidArgumentException('Only local files are supported');
        }
        $result = copy($local->getPath(), $this->getLink($remote));
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
        $result = copy($this->getLink($remote), $local->getPath());
        if (!$result) {
            throw new Exception('Cannot download');
        }

        return $this;
    }
}
