<?php

namespace Tickets\Reports\Domain;

class ReportHandlerRegistry
{
    private array $handlers = [];

    public function register(ReportHandlerInterface $handler): void
    {
        $this->handlers[$handler->getType()] = $handler;
    }

    public function get(string $type): ?ReportHandlerInterface
    {
        return $this->handlers[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->handlers[$type]);
    }

    public function getTypes(): array
    {
        return array_keys($this->handlers);
    }

    public function all(): array
    {
        return $this->handlers;
    }
}
