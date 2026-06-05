<!DOCTYPE html>
<html lang="id">

<head>

  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

  <title>Ulasan - Wandee</title>

  <!-- CSS -->
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">

<!-- FONT -->

  <link rel="preconnect" href="https://fonts.googleapis.com">

  <link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">

  <!-- LUCIDE -->

  <script src="https://unpkg.com/lucide@latest"></script>

</head>

<body class="review-page">
  <?php require __DIR__ . '/../partials/navbar.php'; ?>


  <!-- ===================================== -->
  <!-- REVIEW -->
  <!-- ===================================== -->

  <main class="review-shell">

    <section class="review-layout-new">

      <div class="review-side">

        <h1>
          Beri Ulasan
        </h1>

        <p>
          Ceritakan perjalanan luar biasa kamu kepada komunitas penjelajah kami.
        </p>

        <?php if($reviewMessage): ?>
          <div class="alert alert-success"><?= htmlspecialchars($reviewMessage) ?></div>
        <?php endif; ?>

        <?php if($reviewError): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($reviewError) ?></div>
        <?php endif; ?>

        <?php if($selectedBooking): ?>
        <article class="review-trip-preview">

          <div class="review-trip-image">
            <img src="/wandee/public/assets/img/<?= htmlspecialchars($selectedBooking['destination_image']) ?>" alt="<?= htmlspecialchars($selectedBooking['destination_title']) ?>">
            <span><?= htmlspecialchars($selectedBooking['destination_rating'] ?? '5.0') ?></span>
          </div>

          <div>
            <small>
              <i data-lucide="map-pin"></i>
              <?= htmlspecialchars($selectedBooking['destination_location']) ?>
            </small>

            <h2>
              <?= htmlspecialchars($selectedBooking['destination_title']) ?>
            </h2>

            <p>
              <i data-lucide="calendar-days"></i>
              <?= htmlspecialchars($selectedBooking['destination_trip_date'] ?: 'Perjalanan selesai') ?>
            </p>
          </div>

        </article>
        <?php else: ?>
          <article class="review-trip-preview">
            <div class="review-trip-image">
              <img src="/wandee/public/assets/img/gunung.png" alt="Belum ada perjalanan selesai">
              <span>5.0</span>
            </div>
            <div>
              <small><i data-lucide="lock"></i> Belum tersedia</small>
              <h2>Belum ada trip selesai</h2>
              <p><i data-lucide="info"></i> Ulasan terbuka setelah perjalanan selesai.</p>
            </div>
          </article>
        <?php endif; ?>

        <blockquote class="review-quote">
          "Setiap perjalanan memiliki cerita. Kata-kata Anda dapat menginspirasi
          petualangan berikutnya bagi orang lain."
        </blockquote>

      </div>

      <section class="review-form-card review-form-card-new">

        <div class="review-form-head">

          <h2>
            Bagaimana pengalaman Anda?
          </h2>

          <p>
            Pilih rating Anda (1-5 bintang)
          </p>

        </div>

        <div class="rating-stars" aria-label="Pilih rating">

          <button type="button" aria-label="1 bintang" data-rating="1">
            <i data-lucide="star"></i>
          </button>

          <button type="button" aria-label="2 bintang" data-rating="2">
            <i data-lucide="star"></i>
          </button>

          <button type="button" aria-label="3 bintang" data-rating="3">
            <i data-lucide="star"></i>
          </button>

          <button type="button" aria-label="4 bintang" data-rating="4">
            <i data-lucide="star"></i>
          </button>

          <button type="button" aria-label="5 bintang" data-rating="5">
            <i data-lucide="star"></i>
          </button>

        </div>

        <form
          action="/wandee/user/submit_review"
          method="POST"
          enctype="multipart/form-data">

          <input type="hidden" name="rating" id="ratingValue" value="0">

          <div class="review-label-row">
            <label for="destination_id">
              Pilih perjalanan selesai
            </label>
          </div>

          <select
            id="destination_id"
            name="destination_id"
            class="review-select"
            <?= empty($eligibleBookings) ? 'disabled' : '' ?>
            required>
            <?php if(empty($eligibleBookings)): ?>
              <option value="">Belum ada destinasi yang bisa diulas</option>
            <?php else: ?>
              <?php foreach($eligibleBookings as $booking): ?>
                <option value="<?= (int)$booking['destination_id'] ?>">
                  <?= htmlspecialchars($booking['destination_title']) ?> - <?= htmlspecialchars($booking['destination_location']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>

          <div class="review-label-row">
            <label for="reviewText">
              Tulis ulasan Anda
            </label>

            <span>
              Maksimal 500 karakter
            </span>
          </div>

          <textarea
            id="reviewText"
            name="review"
            class="review-textarea"
            maxlength="500"
            placeholder="Bagikan detail tentang pemandu, akomodasi, pemandangan, dan lainnya..."
            <?= empty($eligibleBookings) ? 'disabled' : '' ?>
            required></textarea>

          <label class="review-upload">
            <input type="file" id="reviewPhoto" name="review_photo" accept="image/png,image/jpeg" <?= empty($eligibleBookings) ? 'disabled' : '' ?> onchange="document.getElementById('selectedFileName').innerHTML=this.files.length?'File dipilih: '+this.files[0].name:'';">
            <span>
              <i data-lucide="upload-cloud"></i>
            </span>
            <strong>
              Klik untuk unggah atau seret foto ke sini
            </strong>

            <div id="selectedFileName" style="margin-top:8px;font-size:13px;color:#4ade80;"></div>

            <small>
              PNG, JPG (Maks. 5MB)
            </small>
          </label>

          <button
            type="submit"
            class="btn-primary review-submit"
            <?= empty($eligibleBookings) ? 'disabled' : '' ?>>

            <?= empty($eligibleBookings) ? 'Belum Bisa Mengulas' : 'Kirim Ulasan' ?>
            <i data-lucide="send"></i>

          </button>

        </form>

      </section>

    </section>

  </main>
  <?php require __DIR__ . '/../partials/footer.php'; ?>

  <!-- JS -->

  <script src="/wandee/public/assets/js/script.js"></script>
  
  <script>
    const ratingInput = document.getElementById('ratingValue');
    const ratingButtons = document.querySelectorAll('.rating-stars button');
    
    ratingButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const rating = Number(button.dataset.rating);
        ratingInput.value = rating;
        ratingButtons.forEach((item) => {
          item.classList.toggle('active', Number(item.dataset.rating) <= rating);
        });
      });
    });
  </script>

<script>
const reviewPhotoInput = document.getElementById('reviewPhoto');
const selectedFileName = document.getElementById('selectedFileName');

if (reviewPhotoInput && selectedFileName) {
    reviewPhotoInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            selectedFileName.textContent = 'File dipilih: ' + this.files[0].name;
        } else {
            selectedFileName.textContent = '';
        }
    });
}
</script>

</body>
</html>
