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

    public function process(): bool
    {
        $balance = (float) ($_SESSION['balance'] ?? 0.0);

        $newBalance = match ($this->type) {
            'deposit' => $balance + $this->amount,
            'penarikan' => $balance - $this->amount,
            default => throw new InvalidArgumentException("Jenis transaksi tidak dikenal: {$this->type}"),
        };

        if ($this->type === 'penarikan' && $newBalance < 0) {
            return false;
        }

        $_SESSION['balance'] = $newBalance;

        return true;
    }
}
