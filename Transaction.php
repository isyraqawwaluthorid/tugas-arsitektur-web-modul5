<?php

declare(strict_types=1);

class Transaction
{
    public function __construct(
        private readonly int $id,
        private readonly string $type,
        private readonly float $amount
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
