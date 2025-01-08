<?php

namespace App\Mapper;

use App\Dto\FileDto;
use App\Entity\File;

class FileMapper
{
    /**
     * Converts a File entity to a FileDto.
     *
     * @param File $file The File entity.
     *
     * @return FileDto The mapped DTO.
     */
    public static function entityToDto(File $file): FileDto
    {
        return (new FileDto())
            ->setId($file->getId())
            ->setFileName($file->getFileName())
            ->setFilePath($file->getFilePath())
            ->setFileSize($file->getFileSize())
            ->setUploadDate($file->getUploadDate())
            ->setMimeType($file->getMimeType())
            ->setDownloadCount($file->getDownloadCount());
    }
}
