<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Admin - Wandee</title>

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
      <div class="admin-topbar">
        <div class="admin-title">
          <h1>Profil Admin</h1>
          <p>Atur detail akun admin dan perbarui informasi profil.</p>
        </div>

        <div class="admin-user">
          <?php if(!empty($user['photo'])) : ?>
            <img src="/wandee/public/uploads/profile/<?php echo $user['photo']; ?>" alt="Admin">
          <?php else : ?>
            <div class="admin-user-empty">
              <i data-lucide="user"></i>
            </div>
          <?php endif; ?>
          <div>
            <strong><?php echo $user['name']; ?></strong>
            <p><?php echo $user['email']; ?></p>
          </div>
        </div>
      </div>

      <section class="admin-section">
        <div class="admin-section-head">
          <h3>Detail Profil</h3>
          <p>Gunakan halaman ini untuk melihat dan mengubah profil admin.</p>
        </div>

        <div class="profile-info-grid profile-grid-margin">
          <div class="profile-info-item">
            <span>Nama Lengkap</span>
            <strong><?php echo $user['name']; ?></strong>
          </div>
          <div class="profile-info-item">
            <span>Email</span>
            <strong><?php echo $user['email']; ?></strong>
          </div>
          <div class="profile-info-item">
            <span>Role</span>
            <strong><?php echo ucfirst($user['role']); ?></strong>
          </div>
          <div class="profile-info-item">
            <span>Bergabung Sejak</span>
            <strong><?php echo date('d F Y', strtotime($user['created_at'])); ?></strong>
          </div>
        </div>

        <div class="admin-section">
          <div class="admin-section-head">
            <h3>Perbarui Profil</h3>
            <p>Edit nama, email, password, dan foto profil admin.</p>
          </div>

          <form action="/wandee/user/update_profile" method="POST" enctype="multipart/form-data" class="admin-profile-form-layout">
            <input type="hidden" name="action" value="update_profile">

            <div class="admin-profile-grid-fields">
              <div class="profile-field">
                <label>Nama Lengkap</label>
                <input type="text" name="name" value="<?php echo $user['name']; ?>" required>
              </div>
              <div class="profile-field">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
              </div>
            </div>

            <div class="profile-field full-span">
              <label>Foto Profil</label>
              <label class="file-upload-box" for="adminProfilePhoto">
                <span class="file-upload-button">Choose File</span>
                <span class="file-upload-name" id="adminProfilePhotoName">No file chosen</span>
              </label>
              <input
                type="file"
                name="photo"
                id="adminProfilePhoto"
                class="file-upload-input">
            </div>

            <div class="profile-field full-span">
              <label>Password Baru</label>
              <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti">
            </div>

            <div class="profile-save">
              <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </section>
    </main>
  </div> 

  <?php require __DIR__ . '/../partials/logout_modal.php'; ?>
  <?php require __DIR__ . '/../partials/upload_lightbox.php'; ?>

  <script>
    const adminProfilePhoto = document.getElementById('adminProfilePhoto');
    const adminProfilePhotoName = document.getElementById('adminProfilePhotoName');

    if (adminProfilePhoto && adminProfilePhotoName) {
      adminProfilePhoto.addEventListener('change', () => {
        adminProfilePhotoName.textContent = adminProfilePhoto.files[0]?.name || 'No file chosen';
      });
    }

    lucide.createIcons();
  </script>
</body>
</html>
