<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Perjalanan - Wandee</title>
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="trip-page">
  <?php require __DIR__ . '/../partials/navbar.php'; ?>

  <main class="trip-wrap">
    <section class="trip-hero">
      <h1>Riwayat Perjalanan</h1>
      <p>Semua pemesanan dan status perjalanan Anda tersimpan di sini.</p>
    </section>

    <?php if(empty($bookings)): ?>
      <div class="empty-state-card">
        <p>Anda belum mengikuti perjalanan apapun.</p>
        <a href="/wandee/" class="btn-primary">Cari Trip Baru</a>
      </div>
    <?php else: ?>
      <section class="trip-history-list">
        <?php foreach($bookings as $booking): ?>
          <?php
            $paymentStatus = $booking['payment_status_detail'] ?? $booking['payment_status'];
            $paymentLabel = ucfirst($paymentStatus);
            $tripLabel = ucfirst($booking['trip_status'] ?? 'new');
          ?>
          <article class="trip-history-card">
            <img src="/wandee/public/assets/img/<?= htmlspecialchars($booking['destination_image']) ?>" alt="<?= htmlspecialchars($booking['destination_title']) ?>">
            <div class="trip-history-info">
              <h3><?= htmlspecialchars($booking['destination_title']) ?></h3>
              <p class="trip-location"><i data-lucide="map-pin"></i><?= htmlspecialchars($booking['destination_location']) ?></p>
              <div class="trip-history-meta">
                <span><i data-lucide="users"></i><?= (int)$booking['total_people'] ?> orang</span>
                <span><i data-lucide="route"></i><?= htmlspecialchars($tripLabel) ?></span>
                <span><i data-lucide="credit-card"></i><?= htmlspecialchars($paymentLabel) ?></span>
              </div>
            </div>
            <div class="trip-history-action">
              <span class="trip-status"><?= htmlspecialchars($paymentLabel) ?></span>
              <div class="trip-action-buttons">
                <?php if($paymentStatus === 'waiting' || $paymentStatus === 'pending'): ?>
                  <a href="/wandee/user/payment_detail?booking_id=<?= (int)$booking['id'] ?>&payment_id=<?= (int)$booking['payment_id'] ?>" class="btn-primary">Lengkapi Pembayaran</a>
                <?php else: ?>
                  <a href="/wandee/user/payment_status?booking_id=<?= (int)$booking['id'] ?>" class="btn-secondary">Lihat Status</a>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <?php require __DIR__ . '/../partials/footer.php'; ?>
  <script src="/wandee/public/assets/js/script.js"></script>
  <script>lucide.createIcons();</script>
</body>
</html>
