<?php

namespace App\Mapper;

use App\Dto\LinkDto;
use App\Entity\Link;

class LinkMapper
{
    /**
     * Converts a Link entity to a LinkDto.
     *
     * @param Link $link The Link entity.
     *
     * @return LinkDto The mapped DTO.
     */
    public static function entityToDto(Link $link): LinkDto
    {
        $dto = (new LinkDto())
            ->setId($link->getId())
            ->setToken($link->getToken())
            ->setExpiresAt($link->getExpiresAt())
            ->setPassword($link->getPassword());

        if ($link->getFile()) {
            $dto->setFile(FileMapper::entityToDto($link->getFile()));
        }

        return $dto;
    }
}