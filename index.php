<?php
// index.php
require_once __DIR__ . '/config.php';

// cek apakah pivot table masakan_bahan ada (untuk many-to-many)
$hasPivot = false;
try {
    $res = $pdo->query("SHOW TABLES LIKE 'masakan_bahan'")->fetch();
    $hasPivot = (bool)$res;
} catch (Exception $e) {
    $hasPivot = false;
}

if ($hasPivot) {
    $stmt = $pdo->query("SELECT m.id, m.nama AS nama_masakan, m.deskripsi, m.waktu_memasak, m.created_at
                         FROM masakan m ORDER BY m.created_at DESC");
} else {
    // fallback ke tabel 'makanan' (schema lama sesuai screenshot)
    $stmt = $pdo->query("SELECT id, nama_makanan, deskripsi, waktu_memasak, createAdd FROM makanan ORDER BY createAdd DESC");
}
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar Resep</title>
  <link rel="stylesheet" href="./Assets/css/style.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <div class="container">
    <header>
      <h1>Daftar Resep</h1>
      <p><a class="btn" href="new_post.php">+ Tambah Resep</a> <a class="btn" href="manage_bahan.php">Kelola Bahan</a></p>
    </header>

    <?php if (!$rows): ?>
      <div class="post-card"><p>Belum ada resep. Klik "Tambah Resep".</p></div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <article class="post-card">
          <?php if ($hasPivot): ?>
            <h2><a href="view_post.php?id=<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['nama_masakan']); ?></a></h2>
            <div class="post-meta">Waktu: <?php echo $r['waktu_memasak'] ?: '-'; ?> menit · Dibuat: <?php echo $r['created_at']; ?></div>
            <p>
              <a class="btn" href="view_post.php?id=<?php echo $r['id']; ?>">Lihat</a>
              <a class="btn" href="new_post.php?id=<?php echo $r['id']; ?>">Edit</a>
            </p>
          <?php else: ?>
            <h2><a href="view_post.php?id=<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['nama_makanan']); ?></a></h2>
            <div class="post-meta">Waktu: <?php echo $r['waktu_memasak'] ?: '-'; ?> menit · Dibuat: <?php echo $r['createAdd']; ?></div>
            <p>
              <a class="btn" href="view_post.php?id=<?php echo $r['id']; ?>">Lihat</a>
              <a class="btn" href="new_post.php?id=<?php echo $r['id']; ?>">Edit</a>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>