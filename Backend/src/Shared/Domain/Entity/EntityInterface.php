<?php

namespace Tickets\Shared\Domain\Entity;

interface EntityInterface
{
    /**
     * Создания сущности из массива
     */
    public static function fromState(array $data): self;

    /**
     * Преобразовать значения сущности в массив
     */
    public function toArray(): ?array;

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
}
