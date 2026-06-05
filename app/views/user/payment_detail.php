<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Pembayaran - Wandee</title>
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
    <h1>Detail Pembayaran</h1>
    <p>Ikuti instruksi untuk menyelesaikan pembayaran Anda.</p>
  </section>

  <section class="payment-detail-card">
    <div class="payment-info">
      <h2><?= htmlspecialchars($destination['title']) ?></h2>
      <p><?= htmlspecialchars($destination['location']) ?></p>
      <div class="payment-detail-rows">
        <p><span>Jumlah orang</span><strong><?= (int)$booking['total_people'] ?> Orang</strong></p>
        <p><span>Harga per orang</span><strong>Rp <?= number_format((int)$price, 0, ',', '.') ?></strong></p>
        <p><span>Total harga</span><strong>Rp <?= number_format((int)$payment['payment_amount'], 0, ',', '.') ?></strong></p>
        <p><span>Kode voucher</span><strong><?= $voucher_code !== '' && $voucher_code !== '0' ? htmlspecialchars($voucher_code) : '-' ?></strong></p>
        <p class="payment-detail-total"><span>Total harus dibayar</span><strong>Rp <?= number_format((int)$payment_total, 0, ',', '.') ?></strong></p>
        <p><span>Status pembayaran</span><strong><?= htmlspecialchars(ucfirst($payment['payment_status'])) ?></strong></p>
      </div>
    </div>

    <div class="payment-instructions">
      <h3>Informasi Transfer</h3>
      <?php
        $bankAccounts = [
          'bca' => 'BCA - 1234567890 (a/n Wandee Travel)',
          'bri' => 'BRI - 0987654321 (a/n Wandee Travel)',
          'bni' => 'BNI - 1122334455 (a/n Wandee Travel)',
          'qris' => 'Scan QRIS yang tersedia di aplikasi dompet digital Anda'
        ];
        $method = $payment['payment_method'];
      ?>
      <p><?= htmlspecialchars($bankAccounts[$method] ?? 'Silakan pilih metode pembayaran di halaman sebelumnya.') ?></p>

      <?php if($payment['payment_status'] !== 'verified'): ?>
        <?php if(!empty($payment['payment_proof'])): ?>
          <div class="proof-preview">
            <h3>Bukti Pembayaran Tersimpan</h3>
            <img src="/wandee/public/uploads/payments/<?= htmlspecialchars($payment['payment_proof']) ?>" alt="Bukti Pembayaran">
            <div class="alert alert-warning">
              Bukti pembayaran sudah terkirim dan sedang menunggu verifikasi admin.
            </div>
          </div>
        <?php endif; ?>

        <h3><?= !empty($payment['payment_proof']) ? 'Ganti Bukti Pembayaran' : 'Unggah Bukti Pembayaran' ?></h3>
        <p><?= !empty($payment['payment_proof']) ? 'Upload ulang hanya jika bukti sebelumnya salah. File terbaru akan menggantikan bukti yang dilihat admin.' : 'Upload bukti transfer untuk mempercepat validasi. Maksimal 5 MB, format JPG/JPEG/PNG.' ?></p>
        <form action="/wandee/user/payment_upload" method="POST" enctype="multipart/form-data" class="payment-proof-form">
          <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
          <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
          <div class="form-group">
            <label for="payment_proof">Bukti Pembayaran</label>
            <input type="file" id="payment_proof" name="payment_proof" accept="image/jpeg,image/png" required>
          </div>
          <div class="form-group">
            <button type="submit" class="btn-primary"><?= !empty($payment['payment_proof']) ? 'Ganti Bukti' : 'Upload Bukti' ?></button>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-success">
          Pembayaran sudah diverifikasi. Terima kasih! Silakan cek status perjalanan Anda di halaman riwayat.
        </div>
      <?php endif; ?>

      <a href="/wandee/user/payment_status?booking_id=<?= (int)$booking['id'] ?>" class="btn-secondary">Lihat Status Pembayaran</a>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../partials/upload_lightbox.php'; ?>
<script src="/wandee/public/assets/js/script.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
