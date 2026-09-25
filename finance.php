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
</body>
</html>
