<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Ulasan - Wandee Admin</title>
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

    <!-- TOPBAR -->
    <div class="admin-topbar">
      <div class="admin-title">
        <h1>Manajemen Ulasan</h1>
        <p>Moderasi ulasan dan penilaian yang dikirimkan oleh pengguna untuk menjaga kualitas konten.</p>
      </div>

      <div class="admin-user">
        <div class="admin-user-empty">
          <i data-lucide="user"></i>
        </div>
        <div>
          <strong><?php echo $user['name']; ?></strong>
          <p><?php echo $user['email']; ?></p>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="manage-table-wrap">
      <?php if (empty($reviews)): ?>
        <div class="dest-empty dest-empty-notice">
          <p>Belum ada ulasan yang ditambahkan oleh pengguna.</p>
        </div>
      <?php else: ?>
        <table class="manage-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Pengguna</th>
              <th>Destinasi Wisata</th>
              <th>Rating</th>
              <th class="review-text-header">Ulasan</th>
              <th>Foto Bukti</th>
              <th>Tanggal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reviews as $review): ?>
              <tr>
                <td>#<?= (int)$review['id'] ?></td>
                <td><strong><?= htmlspecialchars($review['user_name']) ?></strong></td>
                <td><?= htmlspecialchars($review['destination_title']) ?></td>
                <td>
                  <span class="badge-rating-stars">
                    ★ <?= (int)$review['rating'] ?>.0
                  </span>
                </td>
                <td class="review-text-cell">
                  <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                </td>
                <td>
                  <?php if (!empty($review['review_image'])): ?>
                    <?php $reviewImageUrl = '/wandee/public/uploads/reviews/' . htmlspecialchars($review['review_image']); ?>
                    <button
                      type="button"
                      class="review-image-preview-btn"
                      data-review-image-src="<?= $reviewImageUrl ?>"
                      data-review-image-title="Foto Ulasan #<?= (int)$review['id'] ?>"
                      title="Klik untuk memperbesar"
                    >
                      <img src="/wandee/public/uploads/reviews/<?= htmlspecialchars($review['review_image']) ?>" alt="Foto Ulasan" class="review-image-thumb">
                    </button>
                  <?php else: ?>
                    <span class="text-italic-muted">Tidak ada</span>
                  <?php endif; ?>
                </td>
                <td><small class="review-date-text"><?= date('d M Y', strtotime($review['created_at'])) ?></small></td>
                <td>
                  <div class="margin-zero">
                    <button type="button" class="btn-danger-verif" data-delete-review data-review-id="<?= (int)$review['id'] ?>">
                      <i data-lucide="trash-2" class="btn-icon-svg"></i> Hapus
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </main>
</div>

<?php require __DIR__ . '/../partials/logout_modal.php'; ?>
<?php require __DIR__ . '/../partials/confirm_delete_modal.php'; ?>

<div class="lightbox-modal" id="reviewImageModal" aria-hidden="true" role="dialog" aria-label="Preview foto ulasan">
  <button type="button" class="lightbox-close" id="reviewImageClose" aria-label="Tutup preview">
    <i data-lucide="x"></i>
  </button>
  <img src="" alt="Preview foto ulasan" class="lightbox-content" id="reviewImagePreview">
</div>

<script>
  lucide.createIcons();

  const reviewImageModal = document.getElementById('reviewImageModal');
  const reviewImagePreview = document.getElementById('reviewImagePreview');
  const reviewImageClose = document.getElementById('reviewImageClose');

  document.querySelectorAll('.review-image-preview-btn').forEach(button => {
    button.addEventListener('click', () => {
      reviewImagePreview.src = button.dataset.reviewImageSrc;
      reviewImagePreview.alt = button.dataset.reviewImageTitle || 'Preview foto ulasan';
      reviewImageModal.classList.add('is-open');
      reviewImageModal.setAttribute('aria-hidden', 'false');
    });
  });

  function closeReviewImageModal()
  {
    reviewImageModal.classList.remove('is-open');
    reviewImageModal.setAttribute('aria-hidden', 'true');
    reviewImagePreview.src = '';
  } 

  reviewImageClose.addEventListener('click', closeReviewImageModal);
  reviewImageModal.addEventListener('click', event => {
    if (event.target === reviewImageModal) {
      closeReviewImageModal();
    }
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && reviewImageModal.classList.contains('is-open')) {
      closeReviewImageModal();
    }
  });
</script>

</body>
</html>
