<?php
namespace Hyperion\Workflow\CommandDriver\Traits;

/**
 * Functions for remote connections
 */
trait RemoteTrait
{
    /**
     * @var string
     */
    private $pkey_file = null;

    /**
     * Create a file on the filesystem containing the private key file
     *
     * This file will be zeroed and removed, but it's (ephemeral) existence is a security concern.
     *
     * @param string $certificate
     * @return string Filename to private key file
     */
    protected function createPrivateKey($certificate)
    {
        $temp_file = tempnam(sys_get_temp_dir(), 'bakery-');
        chmod($temp_file, 0600);
        file_put_contents($temp_file, $certificate);
        $this->pkey_file = $temp_file;
        return $temp_file;
    }

    /**
     * Remove a generated private key file, if it exists
     */
    protected function cleanPrivateKey()
    {
        if ($this->pkey_file) {
            // Zero the file out first so the data can't be recovered
            $zero = str_repeat(chr(0), filesize($this->pkey_file));
            file_put_contents($this->pkey_file, $zero);

            // Delete
            unlink($this->pkey_file);
        }
    }
} 