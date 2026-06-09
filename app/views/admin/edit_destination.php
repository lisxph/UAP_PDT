<?php
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Destinasi - Wandee Admin</title>
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  
</head>

<body class="admin-page">

<div class="admin-wrap">

  <?php require __DIR__ . '/../partials/admin_sidebar.php'; ?>

  <main class="admin-main">

    <!-- BACK TO LIST -->
    <a href="/wandee/admin/manage" class="btn-action-back">
      <i data-lucide="arrow-left" class="back-arrow-icon"></i>
      Kembali ke Daftar Destinasi
    </a>

    <!-- TOPBAR -->
    <div class="admin-topbar">
      <div class="admin-title">
        <h1>Edit Destinasi</h1>
        <p>Perbarui informasi destinasi wisata yang ada di Wandee.</p>
      </div>
    </div>

    <!-- FORM -->
    <form action="/wandee/destination/update" method="POST" enctype="multipart/form-data" class="manage-form">
      <input type="hidden" name="id" value="<?php echo $data['id']; ?>">

      <?php if (!empty($errors)): ?>
        <div class="form-alert">
          <strong>Periksa kembali input:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="manage-grid">
        <div class="manage-field">
          <label>Nama Destinasi</label>
          <input type="text" name="title" required value="<?= htmlspecialchars($old['title'] ?? $data['title']) ?>">
        </div>

        <div class="manage-field">
          <label>Lokasi</label>
          <input type="text" name="location" required value="<?= htmlspecialchars($old['location'] ?? $data['location']) ?>">
        </div>

        <div class="manage-field">
          <label>Kategori</label>
          <select name="category" required>
            <?php $catVal = $old['category'] ?? $data['category']; ?>
            <option value="Gunung" <?= $catVal === 'Gunung' ? 'selected' : '' ?>>Gunung</option>
            <option value="Pantai" <?= $catVal === 'Pantai' ? 'selected' : '' ?>>Pantai</option>
            <option value="Air Terjun" <?= $catVal === 'Air Terjun' ? 'selected' : '' ?>>Air Terjun</option>
            <option value="Kota" <?= $catVal === 'Kota' ? 'selected' : '' ?>>Kota</option>
          </select>
        </div>

        <div class="manage-field">
          <label>Harga</label>
          <input type="number" step="0.01" min="0" name="price" required value="<?= htmlspecialchars($old['price'] ?? $data['price']) ?>">
        </div>

        <div class="manage-field">
          <label>Tanggal Trip</label>
          <input type="text" name="trip_date" value="<?= htmlspecialchars($old['trip_date'] ?? $data['trip_date'] ?? '') ?>">
        </div>

        <div class="manage-field">
          <label>Rating Akumulasi</label>
          <input type="number" step="0.1" name="rating" readonly class="rating-readonly-field" value="<?= htmlspecialchars($data['rating']) ?>">
        </div>

        <div class="manage-field full-span">
          <label>Deskripsi</label>
          <textarea name="description" required><?= htmlspecialchars($old['description'] ?? $data['description']) ?></textarea>
        </div>

        <div class="manage-field full-span">
          <label>Ganti Gambar (Opsional)</label>
          <label class="file-upload-box" for="destinationImage">
            <span class="file-upload-button">Choose File</span>
            <span class="file-upload-name" id="destinationImageName">No file chosen</span>
          </label>
          <input type="file" name="image" id="destinationImage" class="file-upload-input" accept=".jpg,.jpeg,.png">
          <span class="help-text-block">
            Format JPG / JPEG / PNG, maksimal 5 MB. Kosongkan jika tidak ingin mengganti gambar.
          </span>
        </div>
      </div>

      <button type="submit" class="btn-primary btn-action-save">Update Destinasi</button>
    </form>

  </main>
</div>

<?php require __DIR__ . '/../partials/logout_modal.php'; ?>
 
<script>
  const destinationImage = document.getElementById('destinationImage');
  const destinationImageName = document.getElementById('destinationImageName');

  if (destinationImage && destinationImageName) {
    destinationImage.addEventListener('change', () => {
      destinationImageName.textContent = destinationImage.files[0]?.name || 'No file chosen';
    });
  }

  lucide.createIcons();
</script>

</body>
</html>
