<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // create or update
    $name = trim($_POST['nama_bahan'] ?? '');
    if ($name !== '') {
        if (!empty($_POST['id'])) {
            $pdo->prepare("UPDATE bahan SET nama_bahan = ?, updateAdd = NOW() WHERE id = ?")->execute([$name, (int)$_POST['id']]);
        } else {
            $pdo->prepare("INSERT INTO bahan (nama_bahan, createAdd) VALUES (?, NOW())")->execute([$name]);
        }
    }
    header('Location: manage_bahan.php');
    exit;
}

if (!empty($_GET['delete'])) {
    $pdo->prepare("DELETE FROM bahan WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: manage_bahan.php'); exit;
}

$bahan = $pdo->query("SELECT * FROM bahan ORDER BY nama_bahan")->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Kelola Bahan</title><link rel="stylesheet" href="./Assets/css/style.css"></head>
<body>
<div class="container">
  <header><h1>Kelola Bahan</h1><p><a class="btn" href="index.php">Kembali</a></p></header>

  <div class="post-card">
    <form method="post">
      <label>Nama Bahan</label>
      <input type="text" name="nama_bahan" required>
      <button class="btn" type="submit">Tambah</button>
    </form>
  </div>

  <div class="post-card">
    <h2>Daftar Bahan</h2>
    <ul>
      <?php foreach ($bahan as $b): ?>
        <li>
          <?php echo htmlspecialchars($b['nama_bahan']); ?>
          <a class="btn" href="manage_bahan.php?delete=<?php echo $b['id']; ?>" onclick="return confirm('Hapus?')">Hapus</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
</body>
</html>
