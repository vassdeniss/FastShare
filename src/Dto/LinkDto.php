<?php

namespace App\Dto;

use DateTimeInterface;

class LinkDto
{
    private ?string $id = null;
    private ?string $token = null;
    private ?DateTimeInterface $expiresAt = null;
    private ?FileDto $file = null;
    private ?string $password = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getFile(): ?FileDto
    {
        return $this->file;
    }

    public function setFile(?FileDto $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }
}
