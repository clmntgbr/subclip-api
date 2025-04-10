<?php

namespace App\Service;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\Uid\Uuid;

class FileService
{
    public function __construct(
        private FilesystemOperator $awsStorage,
    ) {
    }

    public function downloadFromS3(Uuid $userId, Uuid $clipId, string $fileName): string
    {
        $path = sprintf('%s/%s/%s', $userId->__toString(), $clipId->__toString(), $fileName);
        $localPath = sprintf('public/tmp/%s', $path);

        $dirPath = dirname($localPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $stream = $this->awsStorage->readStream($path);

        if (!$stream) {
            throw new \Exception('Failed to open stream for reading');
        }

        $localFile = fopen($localPath, 'w');
        stream_copy_to_stream($stream, $localFile);
        fclose($localFile);
        fclose($stream);

        return str_replace('public/', '', $localPath);
    }

    public function removeLocalFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        if (!unlink($filePath)) {
            return false;
        }

        return true;
    }

    public function removeLocalDirectory(string $dirPath): bool
    {
        if (!is_dir($dirPath)) {
            throw new \Exception('Directory does not exist: '.$dirPath);
        }

        $files = array_diff(scandir($dirPath), ['.', '..']);

        foreach ($files as $file) {
            $path = $dirPath.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path)) {
                $this->removeLocalDirectory($path);
            } else {
                if (!unlink($path)) {
                    throw new \Exception('Failed to delete file: '.$path);
                }
            }
        }

        if (!rmdir($dirPath)) {
            throw new \Exception('Failed to delete directory: '.$dirPath);
        }

        return true;
    }
}
