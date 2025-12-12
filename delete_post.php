<?php
require_once __DIR__ . '/config.php';
if (empty($_GET['id'])) { header('Location: index.php'); exit; }
$id = (int)$_GET['id'];

$hasPivot = (bool)$pdo->query("SHOW TABLES LIKE 'masakan_bahan'")->fetch();

if ($hasPivot) {
    $pdo->prepare("DELETE FROM masakan WHERE id = ?")->execute([$id]);
} else {
    $pdo->prepare("DELETE FROM makanan WHERE id = ?")->execute([$id]);
}
header('Location: index.php');
exit;