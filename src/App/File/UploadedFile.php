<?php

namespace App\File;

use Zend\Diactoros\UploadedFile as DiactorosUploadedFile;

class UploadedFile extends DiactorosUploadedFile
{
    private $isMoved = false;

    public function moveTo($targetPath)
    {
        if ($this->isMoved) {
            throw new \RuntimeException('Cannot move file; already moved!');
        }

        if ($this->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if (! is_string($targetPath) || empty($targetPath)) {
            throw new \InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        $targetDirectory = dirname($targetPath);
        if (! is_dir($targetDirectory) || ! is_writable($targetDirectory)) {
            throw new \RuntimeException(sprintf(
                'The target directory `%s` does not exists or is not writable',
                $targetDirectory
            ));
        }

        $this->moveFile($targetPath);
        $this->isMoved = true;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     * @throws \RuntimeException
     */
    private function moveFile($path)
    {
        $handle = fopen($path, 'wb+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to write to designated path');
        }

        $stream = $this->getStream();
        $stream->rewind();
        while (! $stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
    }
}
