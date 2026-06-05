<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($dest['title'] ?? '') ?> - Wandee</title>
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="detail-page">
  <?php require __DIR__ . '/../partials/navbar.php'; ?>

  <main class="detail-shell">

    <a href="/wandee/#destinations" class="detail-back">
      <i data-lucide="arrow-left"></i>
      Kembali ke Jelajah
    </a>

    <section class="detail-grid-new">

      <!-- KIRI -->
      <div class="detail-left-new">

        <article class="detail-destination-card">

          <div class="detail-image-wrap">

            <form action="/wandee/user/favorite_toggle" method="POST" class="favorite-overlay-form">
              <input type="hidden" name="destination_id" value="<?= (int)$dest['id'] ?>">
              <input type="hidden" name="return_to" value="/user/detail?id=<?= (int)$dest['id'] ?>">
              <button type="submit" class="favorite-overlay-btn <?= $isFavorite ? 'active' : '' ?>">♥</button>
            </form>

            <img
              src="/wandee/public/assets/img/<?= htmlspecialchars($dest['image'] ?? '') ?>"
              alt="<?= htmlspecialchars($dest['title'] ?? '') ?>"
              onerror="this.src='/wandee/public/assets/img/gunung.png'">

            <span class="detail-open-badge">Perjalanan Terbuka</span>
            <span class="detail-rating-badge">★ <?= htmlspecialchars($dest['rating'] ?? '') ?></span>
          </div>

          <div class="detail-destination-body">

            <h1><?= htmlspecialchars($dest['title'] ?? '') ?></h1>

            <p class="detail-rating-line">
              <strong><?= htmlspecialchars($dest['rating'] ?? '') ?></strong>
              <span>Open Trip</span>
              <span><?= htmlspecialchars($dest['category'] ?? '') ?></span>
            </p>

            <div class="detail-info-strip-new">
              <div>
                <i data-lucide="map-pin"></i>
                <span>Lokasi</span>
                <strong><?= htmlspecialchars($dest['location'] ?? '') ?></strong>
              </div>
              <div>
                <i data-lucide="calendar-days"></i>
                <span>Tanggal</span>
                <strong><?= $dest['trip_date'] ? htmlspecialchars($dest['trip_date']) : '-' ?></strong>
              </div>
              <div>
                <i data-lucide="tag"></i>
                <span>Kategori</span>
                <strong><?= htmlspecialchars($dest['category'] ?? '') ?></strong>
              </div>
            </div>

          </div>

        </article>

        <!-- Harga -->
        <section class="detail-price-card">
          <span>Harga Per Orang</span>
          <h2><?= htmlspecialchars($dest['price'] ?? '') ?><small>/orang</small></h2>
          <p>Slot tersedia</p>
        </section>

        <!-- Tentang -->
        <section class="detail-about-card">
          <h2>Tentang Perjalanan</h2>
          <p><?= nl2br(htmlspecialchars($dest['description'] ?? 'Deskripsi belum tersedia.')) ?></p>
        </section>

      </div>

      <!-- KANAN -->
      <div class="detail-right-new">

        <section class="detail-panel detail-itinerary-panel">
          <h2>
            <i data-lucide="map"></i>
            Rencana Perjalanan
          </h2>
          <div class="detail-timeline">
            <?php foreach($itineraries as $item): ?>
              <article>
                <span><?= htmlspecialchars($item['label']) ?></span>
                <h3><?= htmlspecialchars($item['title']) ?></h3>
                <p><?= htmlspecialchars($item['desc']) ?></p>
                <small>
                  <i data-lucide="clock-3"></i>
                  <?= htmlspecialchars($item['time']) ?>
                </small>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="detail-panel">
          <h2>Fasilitas</h2>
          <div class="detail-facilities">
            <?php foreach($facilities as $fac): ?>
              <?php $icon = $facility_icons[$fac] ?? 'check-circle'; ?>
              <span>
                <i data-lucide="<?= $icon ?>"></i>
                <?= htmlspecialchars($fac) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="detail-join-card">
          <div>
            <span>Total Harga</span>
            <strong><?= htmlspecialchars($dest['price'] ?? '') ?></strong>
            <small>/orang</small>
          </div>
          <a href="/wandee/user/payment?id=<?= (int)$dest['id'] ?>&people=1" class="btn-primary">Ikut Perjalanan</a>
        </section>

      </div>

    </section>

  </main>

  <?php require __DIR__ . '/../partials/footer.php'; ?>
  <script src="/wandee/public/assets/js/script.js"></script>
  <script>lucide.createIcons();</script>
</body>
</html>