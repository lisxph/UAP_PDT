<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran - Wandee</title>

  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="payment-page">
  <?php require __DIR__ . '/../partials/navbar.php'; ?>

  <main class="payment-shell">

    <?php if(($_GET['error'] ?? '') === 'deadlock'): ?>
    <div style="
      background:#fef3c7; border:2px solid #f59e0b; border-radius:12px;
      padding:1rem 1.25rem; margin-bottom:1.25rem;
      display:flex; align-items:flex-start; gap:.75rem;">
      <i data-lucide="alert-triangle" style="color:#d97706; flex-shrink:0; margin-top:2px;"></i>
      <div>
        <strong style="color:#92400e; display:block; margin-bottom:.2rem;">
          Terjadi Deadlock — Transaksi Dibatalkan Otomatis
        </strong>
        <span style="font-size:.9rem; color:#78350f;">
          Sistem mendeteksi dua pemesanan destinasi yang sama di waktu bersamaan.
          Transaksi kamu dibatalkan oleh database untuk mencegah konflik data.
          Silakan coba pesan kembali.
        </span>
      </div>
    </div>
    <?php elseif(($_GET['error'] ?? '') === 'booking_failed'): ?>
    <div style="
      background:#fee2e2; border:1px solid #fca5a5; border-radius:12px;
      padding:1rem 1.25rem; margin-bottom:1.25rem;
      display:flex; align-items:flex-start; gap:.75rem;">
      <i data-lucide="x-circle" style="color:#dc2626; flex-shrink:0; margin-top:2px;"></i>
      <div>
        <strong style="color:#991b1b; display:block; margin-bottom:.2rem;">Pemesanan Gagal</strong>
        <span style="font-size:.9rem; color:#7f1d1d;">
          Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.
        </span>
      </div>
    </div>
    <?php endif; ?>

    <section class="section-header">
      <h1>Bayar Perjalanan</h1>
      <p>Periksa detail pembayaran dan pilih metode yang nyaman untuk Anda.</p>
    </section>

    <section class="payment-deadline">
      <div class="payment-deadline-icon">
        <i data-lucide="clock-3"></i>
      </div>
      <p>
        <strong>Selesaikan pembayaran Anda</strong><br>
        Pilih metode pembayaran, lalu lanjutkan untuk melihat rekening dan total transfer.
      </p>
    </section>

    <section class="payment-trip-card">
      <img src="/wandee/public/assets/img/<?= htmlspecialchars($destination['image']) ?>" alt="<?= htmlspecialchars($destination['title']) ?>">
      <div class="payment-trip-info">
          <h1><?= htmlspecialchars($destination['title']) ?></h1>
          <p><?= htmlspecialchars($destination['location']) ?></p>
          <span>Open Trip</span>
      </div>
      <div class="payment-trip-price">
        <div>
          <span>Jumlah orang</span>
          <strong><?= (int)$quantity ?> Orang</strong>
        </div>
        <div>
          <span>Harga per orang</span>
          <strong>Rp <?= number_format((int)$price, 0, ',', '.') ?></strong>
        </div>
        <div class="payment-total-line">
          <span>Total harga</span>
          <strong>Rp <?= number_format((int)$total_price, 0, ',', '.') ?></strong>
        </div>
      </div>
    </section>

    <form action="/wandee/user/payment_init" method="POST" class="payment-form">
      <section class="payment-grid">
        <div class="payment-main">
        <input type="hidden" name="action" value="init">
        <input type="hidden" name="destination_id" value="<?= (int)$destination['id'] ?>">
        <input type="hidden" name="total_people" value="<?= (int)$quantity ?>">

        <div class="payment-panel">
          <h2>Metode Pembayaran</h2>
          <div class="payment-methods">
            <label class="payment-method">
              <input type="radio" name="payment_method" value="bca" required>
              <span class="payment-radio"></span>
              <strong>BCA</strong>
              <span>Bank BCA</span>
            </label>
            <label class="payment-method">
              <input type="radio" name="payment_method" value="bri" required>
              <span class="payment-radio"></span>
              <strong>BRI</strong>
              <span>Bank BRI</span>
            </label>
            <label class="payment-method">
              <input type="radio" name="payment_method" value="bni" required>
              <span class="payment-radio"></span>
              <strong>BNI</strong>
              <span>Bank BNI</span>
            </label>
            <label class="payment-method payment-method-wide">
              <input type="radio" name="payment_method" value="qris" required>
              <span class="payment-radio"></span>
              <strong>QRIS</strong>
              <span>Scan QR code di aplikasi dompet digital Anda</span>
            </label>
          </div>
        </div>
        </div>

        <aside class="payment-summary">
          <h2>Informasi Pesanan</h2>
          <div class="payment-row">
            <span>Harga per orang</span>
            <strong>Rp <?= number_format((int)$price, 0, ',', '.') ?></strong>
          </div>
          <div class="payment-row">
            <span>Jumlah peserta</span>
            <strong><?= (int)$quantity ?> Orang</strong>
          </div>
          <div class="payment-row payment-row-total">
            <span>Total Pembayaran</span>
            <strong>Rp <?= number_format((int)$total_price, 0, ',', '.') ?></strong>
          </div>
          <div class="payment-note">
            <h3>Catatan</h3>
            <p><i data-lucide="info"></i> Jika ada kode voucher, nilainya hanya sebagai catatan dan tidak menambah total pembayaran.</p>
          </div>
          <button type="submit" class="btn-primary payment-pay-button">Lanjutkan ke Instruksi Pembayaran</button>
        </aside>
      </section>
    </form>
  </main>

  <?php require __DIR__ . '/../partials/footer.php'; ?>
  <script>lucide.createIcons();</script>
</body>
</html>
