<?php
declare(strict_types=1);

namespace Shared\Domain\Entity;

use Carbon\Carbon;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Uuid;

abstract class AbstractionEntity implements EntityInterface
{
    /**
     * {@inheritdoc}
     * @throws JsonException
     */
    public function toJson(): string
    {
        $arr = $this->toArray();

        return Json::encode($arr);
    }

    /**
     * Вывести сущность в виде массива
     * @throws JsonException
     */
    public function toArray(): array
    {
        $vars = get_object_vars($this);

        $array = [];
        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                $array[$key] = [];
                foreach ($value as $keyInValue => $item) {
                    if ($item instanceof EntityInterface) {
                        $array[$key][$keyInValue] = $item->toArray();
                    } elseif ($item instanceof EntityDataInterface) {
                        $array[$key][$keyInValue] = Json::decode($item->toJson(), 0);
                    } else {
                        $array[$key][$keyInValue] = $item;
                    }
                }
            } else {
                $this->addItems($array, $key, $value);
            }

        }

        return $array;
    }

    private function addItems(array &$array, string $key, mixed $value): void
    {
        if ($value instanceof EntityInterface) {
            $array[$key] = $value->toArray();
        } elseif ($value instanceof EntityDataInterface || $value instanceof Uuid || $value instanceof Carbon) {
            //TODO: Вынести в отдельный класс, перебросить зависимость на детей
            $array[ltrim($key)] = (string) $value;
        } else {
            $array[ltrim($key)] = $value;
        }
    }

    public function __get(string $name)
    {
        $methodName = "get{$name}";

        return method_exists($this, $methodName) ? $this->$methodName() : null;
    }
}
