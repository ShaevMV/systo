<?php

namespace Shared\Domain\Entity;

interface EntityInterface
{

    /**
     * Преобразовать значения сущности в массив
     */
    public function toArray(): ?array;

    public function toArrayForEdit(): ?array;

    public function toArrayForCreate(): ?array;

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name);

    /**
     * Вывести объект в виде json
     */
    public function toJson(): string;

    public function isNotEmpty(): bool;
}
