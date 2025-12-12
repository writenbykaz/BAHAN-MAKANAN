<?php
// view_post.php
require_once __DIR__ . '/config.php'; // pastikan menghasilkan $pdo (PDO instance)

// helper escape
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Pastikan tabel relasi ada (sesuaikan bila Anda ingin fallback ke struktur lama)
    $hasPivot = (bool)$pdo->query("SHOW TABLES LIKE 'relasi'")->fetch();

    // Ambil data makanan
    $stmt = $pdo->prepare("SELECT id, nama_makanan, deskripsi, waktu_memasak, createAdd, updateAdd FROM makanan WHERE id = ?");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$m) {
        echo "Resep tidak ditemukan. <a href='index.php'>Kembali</a>";
        exit;
    }

    // Ambil bahan terkait lewat pivot relasi
    $bahan = [];
    if ($hasPivot) {
        $bstmt = $pdo->prepare("
            SELECT b.id, b.nama_bahan
            FROM relasi r
            JOIN bahan b ON b.id = r.bahan_id
            WHERE r.makanan_id = ?
            ORDER BY b.nama_bahan
        ");
        $bstmt->execute([$id]);
        $bahan = $bstmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Jika tidak ada pivot tabel, bisa coba fallback (jika Anda punya kolom 'bahan' di makanan)
        // $bahan = []; // tetap kosong
    }

} catch (Exception $e) {
    // Untuk debugging sementara tampilkan error; di production sebaiknya log saja.
    echo "Terjadi kesalahan: " . e($e->getMessage());
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Lihat Resep - <?php echo e($m['nama_makanan']); ?></title>
  <link rel="stylesheet" href="./Assets/css/style.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 900px; }
    h1 { margin-bottom: 6px; }
    .meta { color: #666; font-size: 0.9em; margin-bottom: 14px; }
    .section { margin-top: 18px; }
    ul { padding-left: 20px; }
    .actions { margin-top: 20px; }
    .btn { display:inline-block; padding:6px 10px; border-radius:4px; text-decoration:none; border:1px solid #ccc; background:#f5f5f5; color:#333; }
    .btn-danger { border-color:#e74c3c; color:#fff; background:#e74c3c; }
  </style>
</head>
<body>
  <h1><?php echo e($m['nama_makanan']); ?></h1>
  <div class="meta">
    <?php if (!empty($m['waktu_memasak'])): ?>
      Waktu Memasak: <?php echo e($m['waktu_memasak']); ?> &nbsp;|&nbsp;
    <?php endif; ?>
    Dibuat: <?php echo e($m['createAdd']); ?> &nbsp;|&nbsp; Terakhir diupdate: <?php echo e($m['updateAdd']); ?>
  </div>

  <div class="section">
    <h2>Deskripsi</h2>
    <div><?php echo nl2br(e($m['deskripsi'])); ?></div>
  </div>

  <div class="section">
    <h2>Bahan</h2>
    <?php if (!empty($bahan)): ?>
      <ul>
        <?php foreach ($bahan as $b): ?>
          <li><?php echo e($b['nama_bahan']); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Tidak ada bahan tercatat untuk resep ini.</p>
    <?php endif; ?>
  </div>

  <div class="actions">
    <a class="btn" href="index.php">Kembali</a>
    <a class="btn" href="new_post.php?id=<?php echo (int)$m['id']; ?>">Edit</a>
    <a class="btn btn-danger" href="delete_post.php?id=<?php echo (int)$m['id']; ?>" onclick="return confirm('Yakin ingin menghapus resep ini?')">Hapus</a>
  </div>
</body>
</html>