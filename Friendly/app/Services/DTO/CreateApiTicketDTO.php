<?php

declare(strict_types=1);

namespace App\Services\DTO;

class CreateApiTicketDTO
{
    public function __construct(
        private string $project,
        private string $curator,
        private string $festivalId,
        private string $email,
        private int $userId,
        private string $phone,
        private array $list,
        private array $auto,
        private ?string $comment = null,
    )
    {
    }

    public static function fromState(array $data, int $userId): self
    {
        return new self(
            $data['project'],
            $data['curator'],
            $data['festival_id'],
            $data['email'],
            $userId,
            $data['phone'],
            $data['list'],
            $data['auto'],
            $data['comment'],
        );
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getCurator(): string
    {
        return $this->curator;
    }

    public function getFestivalId(): string
    {
        return $this->festivalId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function getAuto(): array
    {
        return $this->auto;
    }

    public function getComment(): string
    {
        return $this->comment ?? '';
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }
}
