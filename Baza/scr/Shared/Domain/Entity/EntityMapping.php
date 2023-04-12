<?php

declare(strict_types=1);

namespace Baza\Shared\Domain\Entity;

use ReflectionClass;
use ReflectionException;

class EntityMapping
{

    /**
     * @param  EntityInterface[]  $dtoList
     */
    public static function flat(array $dtoList): array
    {
        return array_map(static function (EntityInterface $abstractionEntity) {
            return $abstractionEntity->toArray();
        }, $dtoList);
    }

    /**
     * @param  array  $data
     * @param  class-string<EntityInterface> $classAbstractionEntity
     * @return EntityInterface[]
     * @throws ReflectionException
     */
    public static function fromPrimitives(array $data, string $classAbstractionEntity): array
    {
        self::isValid($classAbstractionEntity);
        $result = [];
        foreach ($data as $datum) {
            $result[] = $classAbstractionEntity::fromState($datum);
        }

        return $result;
    }

    /**
     * @param  class-string  $classAbstractionEntity
     * @return void
     * @throws ReflectionException
     */
    private static function isValid(string $classAbstractionEntity): void
    {
        $obj = new ReflectionClass($classAbstractionEntity);

        if (!$obj->isSubclassOf(EntityInterface::class)) {
            throw new EntityException("$classAbstractionEntity не реализует ".EntityInterface::class);
        }
    }
}
