<?php
// insert_post.php
// Menangani CREATE dan UPDATE untuk tabel makanan dan pivot relasi

require_once 'config.php'; // harus menghasilkan $pdo (PDO instance)

// Simple helper untuk redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Ambil input POST
$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$nama_makanan = isset($_POST['nama_makanan']) ? trim($_POST['nama_makanan']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$waktu_memasak = isset($_POST['waktu_memasak']) ? trim($_POST['waktu_memasak']) : '';
// bahan_id[] dari form (multiple select atau checkbox). Pastikan HTML mengirim array.
$bahan_ids = isset($_POST['bahan_id']) && is_array($_POST['bahan_id']) ? $_POST['bahan_id'] : [];

// Validasi sederhana
$errors = [];
if ($nama_makanan === '') {
    $errors[] = 'Nama makanan wajib diisi.';
}
// (opsional) validasi bahan: pastikan tiap entry integer
$bahan_ids = array_values(array_filter($bahan_ids, function($v) { return $v !== ''; }));
$bahan_ids = array_map('intval', $bahan_ids);

if (!empty($errors)) {
    // Anda bisa menampilkan error di halaman form; di sini kita simple redirect kembali dengan pesan sederhana.
    // Untuk produksi lebih baik simpan error di session dan tampilkan di form.
    echo '<h3>Terjadi kesalahan:</h3><ul>';
    foreach ($errors as $e) {
        echo '<li>' . htmlspecialchars($e) . '</li>';
    }
    echo '</ul><p><a href="new_post.php">Kembali</a></p>';
    exit;
}

try {
    // Gunakan transaction supaya insert ke makanan dan relasi konsisten
    $pdo->beginTransaction();

    $now = (new DateTime())->format('Y-m-d H:i:s');

    if ($id === null) {
        // INSERT baru
        $stmt = $pdo->prepare("
            INSERT INTO makanan (nama_makanan, deskripsi, waktu_memasak, createAdd, updateAdd)
            VALUES (:nama, :deskripsi, :waktu, :createAdd, :updateAdd)
        ");
        $stmt->execute([
            ':nama' => $nama_makanan,
            ':deskripsi' => $deskripsi,
            ':waktu' => $waktu_memasak,
            ':createAdd' => $now,
            ':updateAdd' => $now
        ]);
        $makanan_id = (int)$pdo->lastInsertId();
    } else {
        // UPDATE existing
        $stmt = $pdo->prepare("
            UPDATE makanan
            SET nama_makanan = :nama,
                deskripsi = :deskripsi,
                waktu_memasak = :waktu,
                updateAdd = :updateAdd
            WHERE id = :id
        ");
        $stmt->execute([
            ':nama' => $nama_makanan,
            ':deskripsi' => $deskripsi,
            ':waktu' => $waktu_memasak,
            ':updateAdd' => $now,
            ':id' => $id
        ]);
        $makanan_id = $id;

        // Hapus relasi lama (jika ada) — kita akan insert ulang sesuai bahan yang dikirim
        $del = $pdo->prepare("DELETE FROM relasi WHERE makanan_id = ?");
        $del->execute([$makanan_id]);
    }

    // Insert relasi (many-to-many)
    if (!empty($bahan_ids)) {
        $insRel = $pdo->prepare("INSERT INTO relasi (makanan_id, bahan_id) VALUES (:m_id, :b_id)");
        foreach ($bahan_ids as $b_id) {
            // Optional: skip invalid / zero ids
            $b_id = (int)$b_id;
            if ($b_id <= 0) continue;
            $insRel->execute([
                ':m_id' => $makanan_id,
                ':b_id' => $b_id
            ]);
        }
    }

    $pdo->commit();

    // Redirect ke halaman daftar atau view
    redirect('index.php');

} catch (Exception $e) {
    $pdo->rollBack();
    // Log error di server (file log) untuk debugging; jangan tampilkan pesan error detail ke user di production.
    error_log("Error insert_post.php: " . $e->getMessage());
    echo "<h3>Terjadi kesalahan saat menyimpan data.</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>"; // hapus/ubah di production
    echo '<p><a href="new_post.php">Kembali</a></p>';
    exit;
}
