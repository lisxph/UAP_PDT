<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Pembayaran - Wandee Admin</title>
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
        <h1>Verifikasi Pembayaran</h1>
        <p>Kelola verifikasi bukti transfer pemesanan dan pantau status perjalanan.</p>
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

    <!-- FILTER BAR -->
    <?php $activeFilter = $_GET['filter'] ?? 'all'; ?>
    <div class="admin-filter-bar">
      <a href="/wandee/admin/manage_payments" class="<?= $activeFilter === 'all' ? 'active' : '' ?>">Semua</a>
      <a href="/wandee/admin/manage_payments?filter=waiting" class="<?= $activeFilter === 'waiting' ? 'active' : '' ?>">Menunggu Verifikasi</a>
      <a href="/wandee/admin/manage_payments?filter=verified" class="<?= $activeFilter === 'verified' ? 'active' : '' ?>">Diterima (Verified)</a>
      <a href="/wandee/admin/manage_payments?filter=rejected" class="<?= $activeFilter === 'rejected' ? 'active' : '' ?>">Ditolak</a>
      <a href="/wandee/admin/manage_payments?filter=completed" class="<?= $activeFilter === 'completed' ? 'active' : '' ?>">Trip Selesai</a>
    </div>

    <!-- TABLE -->
    <div class="manage-table-wrap">
      <?php if (empty($payments)): ?>
        <div class="dest-empty dest-empty-notice">
          <p>Tidak ada pembayaran yang sesuai dengan filter ini.</p>
        </div>
      <?php else: ?>
        <table class="manage-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Pemesan</th>
              <th>Destinasi</th>
              <th>Jumlah Peserta</th>
              <th>Total Bayar</th>
              <th>Metode</th>
              <th>Status</th>
              <th>Alasan Penolakan</th>
              <th>Bukti Transfer</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $payment): ?>
              <?php $status = $payment['payment_status'] ?? 'waiting'; ?>
              <tr>
                <td>#<?= (int)$payment['id'] ?></td>
                <td><strong><?= htmlspecialchars($payment['user_name'] ?? 'Guest') ?></strong></td>
                <td><?= htmlspecialchars($payment['destination_title'] ?? '-') ?></td>
                <td><?= (int)$payment['total_people'] ?> Orang</td>
                <td><strong>Rp <?= number_format((int)$payment['payment_amount'], 0, ',', '.') ?></strong></td>
                <td><span class="payment-method-badge"><?= htmlspecialchars(strtoupper($payment['payment_method'])) ?></span></td>
                <td>
                  <?php if (($payment['trip_status'] ?? '') === 'completed'): ?>
                    <span class="status-badge completed">Trip Selesai</span>
                  <?php else: ?>
                    <span class="status-badge <?= $status ?>"><?= $status === 'waiting' ? 'Menunggu' : ($status === 'verified' ? 'Verified' : 'Ditolak') ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if(!empty($payment['rejection_reason'])): ?>
                    <?= htmlspecialchars($payment['rejection_reason']) ?>
                    <?php else: ?>
                      -
                      <?php endif; ?>
                    </td>
                <td>
                  <?php if (!empty($payment['payment_proof'])): ?>
                    <?php $proofUrl = '/wandee/public/uploads/payments/' . htmlspecialchars($payment['payment_proof']); ?>
                    <button
                      type="button"
                      class="payment-proof-preview-btn"
                      data-proof-src="<?= $proofUrl ?>"
                      data-proof-title="Bukti Transfer #<?= (int)$payment['id'] ?>"
                      title="Klik untuk memperbesar"
                    >
                      <img src="/wandee/public/uploads/payments/<?= htmlspecialchars($payment['payment_proof']) ?>" alt="Bukti Transfer" class="payment-proof-img-thumb">
                    </button>
                  <?php else: ?>
                    <span class="text-italic-muted">Belum diunggah</span>
                  <?php endif; ?>
                </td>
                <td>
  <div class="btn-action-wrapper">

    <?php if (
      $status === 'waiting' &&
      ($payment['trip_status'] ?? '') !== 'cancelled'
    ): ?>

      <form action="/wandee/admin/payment_update" method="POST" class="margin-zero">
        <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
        <input type="hidden" name="status" value="verified">

        <button type="submit" class="btn-action-verif">
          <i data-lucide="check" class="btn-icon-svg"></i>
          Terima
        </button>
      </form>

      <button
        type="button"
        class="btn-action-reject reject-btn"
        data-payment-id="<?= (int)$payment['id'] ?>"
      >
        <i data-lucide="x" class="btn-icon-svg"></i>
        Tolak
      </button>

    <?php elseif (($payment['trip_status'] ?? '') === 'cancelled'): ?>

      <span class="status-badge rejected">
        Cancelled
      </span>

    <?php endif; ?>

    <?php if ($status === 'verified' && ($payment['trip_status'] ?? '') !== 'completed'): ?>
      <form action="/wandee/admin/trip_complete" method="POST" class="margin-zero">
        <input type="hidden" name="booking_id" value="<?= (int)$payment['booking_id'] ?>">
        <button type="submit" class="btn-action-done">
          <i data-lucide="check-square" class="btn-icon-svg"></i>
          Tandai Selesai
        </button>
      </form>

      <form action="/wandee/admin/payment_update" method="POST" class="margin-zero">
        <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
        <input type="hidden" name="status" value="rejected">

        <button type="submit" class="btn-action-reject">
          <i data-lucide="x" class="btn-icon-svg"></i>
          Batalkan Verif
        </button>
      </form>
    <?php endif; ?>

    <?php if ($status === 'rejected'): ?>
      <form action="/wandee/admin/payment_update" method="POST" class="margin-zero">
        <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
        <input type="hidden" name="status" value="verified">

        <button type="submit" class="btn-action-verif">
          <i data-lucide="check" class="btn-icon-svg"></i>
          Terima
        </button>
      </form>
    <?php endif; ?>

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
<div class="logout-modal" id="rejectModal" aria-hidden="true">
    <div class="logout-modal-backdrop"></div>

    <div class="logout-dialog">

        <h3>Alasan Penolakan</h3>

        <form action="/wandee/admin/payment_update" method="POST">

            <input
                type="hidden"
                name="payment_id"
                id="rejectPaymentId"
            >

            <input
                type="hidden"
                name="status"
                value="rejected"
            >

            <textarea
                name="rejection_reason"
                required
                placeholder="Masukkan alasan penolakan..."
                style="
                    width:100%;
                    min-height:120px;
                    margin-top:15px;
                    margin-bottom:20px;
                    padding:12px;
                    border-radius:12px;
                "
            ></textarea>

            <div class="logout-actions">
                <button
                    type="submit"
                    class="logout-confirm"
                >
                    Kirim
                </button>

                <button
                    type="button"
                    class="logout-cancel"
                    onclick="closeRejectModal()"
                >
                    Kembali
                </button>
            </div>

        </form>

    </div>
</div>

<div class="lightbox-modal" id="paymentProofModal" aria-hidden="true" role="dialog" aria-label="Preview bukti pembayaran">
  <button type="button" class="lightbox-close" id="paymentProofClose" aria-label="Tutup preview">
    <i data-lucide="x"></i>
  </button>
  <img src="" alt="Preview bukti transfer" class="lightbox-content" id="paymentProofImage">
</div>

<script>
  lucide.createIcons();
  const rejectModal = document.getElementById('rejectModal');
const rejectPaymentId = document.getElementById('rejectPaymentId');
const paymentProofModal = document.getElementById('paymentProofModal');
const paymentProofImage = document.getElementById('paymentProofImage');
const paymentProofClose = document.getElementById('paymentProofClose');

document.querySelectorAll('.reject-btn').forEach(button => {

    button.addEventListener('click', () => {

        rejectPaymentId.value = button.dataset.paymentId;

        rejectModal.classList.add('is-open');

        rejectModal.setAttribute('aria-hidden', 'false');

    });

});

function closeRejectModal()
{
    rejectModal.classList.remove('is-open');
    rejectModal.setAttribute('aria-hidden', 'true');
}

document.querySelectorAll('.payment-proof-preview-btn').forEach(button => {
    button.addEventListener('click', () => {
        paymentProofImage.src = button.dataset.proofSrc;
        paymentProofImage.alt = button.dataset.proofTitle || 'Preview bukti transfer';
        paymentProofModal.classList.add('is-open');
        paymentProofModal.setAttribute('aria-hidden', 'false');
    });
});

function closePaymentProofModal()
{
    paymentProofModal.classList.remove('is-open');
    paymentProofModal.setAttribute('aria-hidden', 'true');
    paymentProofImage.src = '';
}

paymentProofClose.addEventListener('click', closePaymentProofModal);
paymentProofModal.addEventListener('click', event => {
    if (event.target === paymentProofModal) {
        closePaymentProofModal();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && paymentProofModal.classList.contains('is-open')) {
        closePaymentProofModal();
    }
});
</script>

</body>
</html>
