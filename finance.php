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
