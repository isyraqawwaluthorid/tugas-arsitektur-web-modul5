<?php

declare(strict_types=1);

require_once __DIR__ . '/Transaction.php';

session_start();

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}

if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Token CSRF tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $type = (string) ($_POST['type'] ?? '');
        $amountInput = trim((string) ($_POST['amount'] ?? ''));
        $typeLabel = null;

        try {
            $typeLabel = match ($type) {
                'deposit' => 'Deposit',
                'penarikan' => 'Penarikan',
                default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
            };
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amountInput) || (float) $amountInput <= 0) {
            $errors[] = 'Jumlah transaksi harus berupa angka desimal positif.';
        }

        if ($errors === []) {
            $amount = (float) $amountInput;
            $id = count($_SESSION['history']) + 1;
            $transaction = new Transaction($id, $type, $amount);

            if ($transaction->process()) {
                $_SESSION['history'][] = [
                    'id' => $transaction->getId(),
                    'label' => $typeLabel,
                    'amount' => $transaction->getAmount(),
                ];
                $success = "{$typeLabel} sebesar Rp" . number_format($amount, 2, ',', '.') . ' berhasil diproses.';
            } else {
                $errors[] = 'Saldo tidak mencukupi untuk melakukan penarikan.';
            }
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$balance = (float) $_SESSION['balance'];
$history = $_SESSION['history'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sistem Manajemen Keuangan Sederhana</title>
<style>
    :root {
        color-scheme: light dark;
        font-family: system-ui, sans-serif;
    }

    body {
        max-width: 640px;
        margin: 2rem auto;
        padding: 0 1rem;
        line-height: 1.5;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-block: 1.5rem;
        padding: 1rem;
        border: 1px solid #8888;
        border-radius: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        text-align: left;
        padding: 0.4rem 0.6rem;
        border-bottom: 1px solid #8884;
    }

    .errors {
        color: #b91c1c;
    }

    .success {
        color: #15803d;
    }
</style>
</head>
<body>
<h1>Sistem Manajemen Keuangan Sederhana</h1>

<?php if ($errors !== []): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($success !== null): ?>
    <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="finance.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <label for="type">Jenis Transaksi</label>
    <select name="type" id="type" required>
        <option value="deposit">Deposit</option>
        <option value="penarikan">Penarikan</option>
    </select>

    <label for="amount">Jumlah</label>
    <input type="text" name="amount" id="amount" inputmode="decimal" placeholder="0.00" required>

    <button type="submit">Proses Transaksi</button>
</form>

<h2>Sisa Saldo: Rp<?= htmlspecialchars(number_format($balance, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></h2>

<h2>Riwayat Transaksi</h2>
<?php if ($history === []): ?>
    <p>Belum ada transaksi.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Jenis</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>Rp<?= htmlspecialchars(number_format($item['amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
