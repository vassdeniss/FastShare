<?php

namespace App\Dto;

use DateTimeInterface;

class FileDto
{
    private ?string $id = null;
    private ?string $fileName = null;
    private ?string $filePath = null;
    private ?int $fileSize = null;
    private ?DateTimeInterface $uploadDate = null;
    private ?string $mimeType = null;
    private int $downloadCount = 0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getUploadDate(): ?DateTimeInterface
    {
        return $this->uploadDate;
    }

    public function setUploadDate(?DateTimeInterface $uploadDate): self
    {
        $this->uploadDate = $uploadDate;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    public function setDownloadCount(int $downloadCount): self
    {
        $this->downloadCount = $downloadCount;
        return $this;
    }
}
