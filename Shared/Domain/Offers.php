<?php

namespace Shared\Domain;

interface Offers
{
    public function getArticular(): string;
    public function getQuantity(): int;
    public function getPrice(): float|int;
    public function getManufacture(): ?string;
    public function getIdForOrder(): string;
}
