<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Pembayaran - Wandee</title>
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="payment-detail-page">
<?php require __DIR__ . '/../partials/navbar.php'; ?>

<main class="user-page-container">
  <section class="section-header">
    <h1>Status Pembayaran</h1>
    <p>Periksa status pembayaran dan perjalanan Anda.</p>
  </section>

  <section class="payment-status-card">
    <h2><?= htmlspecialchars($destination['title']) ?></h2>
    <p><strong>Status perjalanan:</strong> <?= htmlspecialchars(ucfirst($booking['trip_status'])) ?></p>
    <p><strong>Status pembayaran:</strong> <?= htmlspecialchars(ucfirst($payment['payment_status'])) ?></p>

    <div class="status-details">
      <p><strong>Total bayar:</strong> Rp <?= number_format((int)$payment_total, 0, ',', '.') ?></p>
      <p><strong>Kode voucher:</strong> <?= $voucher_code !== '' && $voucher_code !== '0' ? htmlspecialchars($voucher_code) : '-' ?></p>
      <p><strong>Metode pembayaran:</strong> <?= htmlspecialchars(strtoupper($payment['payment_method'])) ?></p>
    </div>

    <?php if($payment['payment_proof']): ?>
      <div class="proof-preview">
        <h3>Bukti Pembayaran</h3>
        <img src="/wandee/public/uploads/payments/<?= htmlspecialchars($payment['payment_proof']) ?>" alt="Bukti Pembayaran">
      </div>
    <?php endif; ?>

    <?php if($payment['payment_status'] === 'waiting'): ?>
      <div class="alert alert-warning">
        Bukti pembayaran sedang menunggu verifikasi admin.
      </div>
    <?php elseif($payment['payment_status'] === 'verified'): ?>
      <div class="alert alert-success">
        Pembayaran berhasil diverifikasi. Silakan ikuti jadwal perjalanan.
      </div>
    <?php elseif($payment['payment_status'] === 'rejected'): ?>
      <div class="alert alert-danger">
        <strong>Pembayaran ditolak.</strong><br>
        <?php if(!empty($payment['rejection_reason'])): ?>
        <strong>Alasan:</strong>
        <?= htmlspecialchars($payment['rejection_reason']) ?>
        <?php else: ?>
        Mohon unggah ulang bukti pembayaran yang valid.
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <a href="/wandee/user/riwayat" class="btn-primary">
        Kembali ke Riwayat
    </a>
</div>

  </section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../partials/upload_lightbox.php'; ?>
<script src="/wandee/public/assets/js/script.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
