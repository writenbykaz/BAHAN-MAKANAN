<?php
// new_post.php
// Form untuk menambah / mengedit makanan dan memilih bahan (many-to-many via table relasi)

require_once 'config.php'; // harus menghasilkan $pdo (PDO instance)

// Simple helper
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Cek apakah ini edit (ada ?id=)
$makanan = null;
$selectedBahanIds = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Ambil data makanan
    $stmt = $pdo->prepare("SELECT id, nama_makanan, deskripsi, waktu_memasak FROM makanan WHERE id = ?");
    $stmt->execute([$id]);
    $makanan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($makanan) {
        // Ambil bahan yang ter-relasi
        $rb = $pdo->prepare("SELECT bahan_id FROM relasi WHERE makanan_id = ?");
        $rb->execute([$id]);
        $selected = $rb->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($selected) {
            // cast ke int
            $selectedBahanIds = array_map('intval', $selected);
        }
    } else {
        // jika id tidak ditemukan, redirect ke index atau tampilkan pesan singkat
        header('Location: index.php');
        exit;
    }
}

// Ambil semua bahan untuk checkbox
$allBahan = [];
try {
    $b = $pdo->query("SELECT id, nama_bahan FROM bahan ORDER BY nama_bahan");
    $allBahan = $b->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Jika tabel bahan tidak ada atau error, $allBahan tetap kosong
    // Anda bisa log error jika perlu
    error_log("Error fetching bahan: " . $e->getMessage());
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?php echo $makanan ? 'Edit' : 'Tambah'; ?> Makanan</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="./Assets/css/style.css">
  <style>
    /* Minimal styling supaya rapi */
    body { font-family: Arial, sans-serif; margin: 20px; }
    label { display: block; margin-top: 10px; }
    input[type="text"], textarea { width: 100%; max-width: 600px; padding: 8px; }
    .bahan-list { margin-top: 8px; }
    .bahan-item { display: inline-block; margin-right: 12px; }
    .actions { margin-top: 16px; }
    .note { color: #666; font-size: 0.9em; }
  </style>
</head>
<body>
  <h1><?php echo $makanan ? 'Edit' : 'Tambah'; ?> Makanan</h1>

  <form action="insert_post.php" method="post">
    <!-- Jika edit, sertakan id -->
    <?php if ($makanan): ?>
      <input type="hidden" name="id" value="<?php echo (int)$makanan['id']; ?>">
    <?php endif; ?>

    <label for="nama_makanan">Nama Makanan <span style="color:red">*</span></label>
    <input id="nama_makanan" name="nama_makanan" type="text" required
           value="<?php echo e($makanan['nama_makanan'] ?? ''); ?>">

    <label for="deskripsi">Deskripsi</label>
    <textarea id="deskripsi" name="deskripsi" rows="6"><?php echo e($makanan['deskripsi'] ?? ''); ?></textarea>

    <label for="waktu_memasak">Waktu Memasak (mis. 30 menit)</label>
    <input id="waktu_memasak" name="waktu_memasak" type="text"
           value="<?php echo e($makanan['waktu_memasak'] ?? ''); ?>">

    <label>Bahan</label>
    <div class="bahan-list">
      <?php if (empty($allBahan)): ?>
        <p class="note">Belum ada data bahan. Silakan tambahkan bahan di halaman kelola bahan.</p>
      <?php else: ?>
        <?php foreach ($allBahan as $b): 
            $bid = (int)$b['id'];
            $checked = in_array($bid, $selectedBahanIds, true) ? 'checked' : '';
        ?>
          <label class="bahan-item">
            <input type="checkbox" name="bahan_id[]" value="<?php echo $bid; ?>" <?php echo $checked; ?>>
            <?php echo e($b['nama_bahan']); ?>
          </label>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="actions">
      <button type="submit"><?php echo $makanan ? 'Update' : 'Simpan'; ?></button>
      <a href="index.php" style="margin-left:12px">Batal / Kembali</a>
    </div>
  </form>

  <?php if ($makanan): ?>
    <p class="note">Anda sedang mengedit <strong><?php echo e($makanan['nama_makanan']); ?></strong>.</p>
  <?php endif; ?>

</body>
</html>