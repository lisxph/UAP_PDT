<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Wandee</title>
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="admin-page">

<div class="admin-wrap">

  <!-- SIDEBAR -->
  <?php require __DIR__ . '/../partials/admin_sidebar.php'; ?>

  <!-- MAIN -->
  <main class="admin-main">

    <!-- HERO -->
    <div class="admin-header-small">
      <div>
        <span class="admin-badge">Dashboard Admin</span>
        <h1>Selamat datang, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <p>Kelola destinasi wisata, pengguna, transaksi perjalanan, dan seluruh aktivitas Wandee dari dashboard admin.</p>
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
          <strong><?php echo htmlspecialchars($user['name']); ?></strong>
          <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
      </div>
    </div>

    <!-- STATS GRID (4 Columns) -->
    <div class="admin-stats">
      <div class="stat-card">
        <p>Pengguna Terdaftar</p>
        <h2><?php echo $total_users; ?></h2>
        <span class="stat-growth">
          <i data-lucide="users"></i> Semua akun
        </span>
      </div>

      <div class="stat-card">
        <p>Destinasi Aktif</p>
        <h2><?php echo $total_trips; ?></h2>
        <span class="stat-growth">
          <i data-lucide="map-pin"></i> Paket aktif
        </span>
      </div>

      <div class="stat-card">
        <p>Total Pemesanan</p>
        <h2><?php echo $total_bookings; ?></h2>
        <span class="stat-growth">
          <i data-lucide="shopping-bag"></i> Transaksi masuk
        </span>
      </div>

      <div class="stat-card">
        <p>Total Pendapatan</p>
        <h2>Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h2>
        <span class="stat-growth stat-growth-green">
          <i data-lucide="dollar-sign"></i> Verified transfer
        </span>
      </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="charts-grid">
      <!-- Line Chart -->
      <div class="chart-card chart-card-custom">
        <h3 class="chart-card-title">
          <i data-lucide="trending-up" class="badge-small-icon"></i> Tren Pemesanan Bulanan
        </h3>
        <div class="chart-canvas-wrap">
          <canvas id="bookingsChart"></canvas>
        </div>
      </div>

      <!-- Doughnut Chart -->
      <div class="chart-card chart-card-custom">
        <h3 class="chart-card-title">
          <i data-lucide="pie-chart" class="badge-small-icon"></i> Kategori Terpopuler
        </h3>
        <div class="chart-canvas-wrap">
          <canvas id="categoryChart"></canvas>
        </div>
      </div>
    </div>

    <!-- RECENT TRANSACTIONS TABLE -->
    <div class="dashboard-table-card dashboard-table-card-custom">
      <h3 class="chart-card-title">
        <i data-lucide="clock" class="badge-small-icon"></i> Transaksi Pemesanan Terbaru
      </h3>
      <div class="dashboard-table-wrap">
        <table class="dashboard-table-custom">
          <thead>
            <tr class="dashboard-table-tr-header">
              <th class="dashboard-table-th">ID</th>
              <th class="dashboard-table-th">Nama Pemesan</th>
              <th class="dashboard-table-th">Destinasi</th>
              <th class="dashboard-table-th">Jumlah Peserta</th>
              <th class="dashboard-table-th">Total Harga</th>
              <th class="dashboard-table-th">Status Pembayaran</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_bookings)): ?>
              <tr>
                <td colspan="6" class="dashboard-table-empty">Belum ada transaksi pemesanan terbaru.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recent_bookings as $booking): ?>
                <tr class="dashboard-table-tr">
                  <td class="dashboard-table-td">#<?= (int)$booking['id'] ?></td>
                  <td class="dashboard-table-td"><strong><?= htmlspecialchars($booking['user_name'] ?? 'Guest') ?></strong></td>
                  <td class="dashboard-table-td-muted"><?= htmlspecialchars($booking['destination_title'] ?? '-') ?></td>
                  <td class="dashboard-table-td-muted"><?= (int)$booking['total_people'] ?> Orang</td>
                  <td class="dashboard-table-td-accent">Rp <?= number_format((int)$booking['total_price'], 0, ',', '.') ?></td>
                  <td class="dashboard-table-td">
                    <?php $payStatus = $booking['payment_status'] ?? 'pending'; ?>
                    <span class="status-badge <?= $payStatus === 'paid' ? 'paid' : ($payStatus === 'cancelled' ? 'cancelled' : 'pending') ?>">
                      <?= $payStatus === 'paid' ? 'Paid' : ($payStatus === 'cancelled' ? 'Batal' : 'Pending') ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<?php require __DIR__ . '/../partials/logout_modal.php'; ?>
<?php require __DIR__ . '/../partials/upload_lightbox.php'; ?>

<!-- Chart configuration scripts -->
<?php
  // Format labels & values for charts safely
  $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
  $monthCounts = array_fill_keys($months, 0);
  
  foreach ($monthly_stats as $stat) {
      $m = trim($stat['month']);
      // Map Indonesian months to abbreviation if needed
      if ($m === 'May') $m = 'Mei';
      if ($m === 'Aug') $m = 'Agt';
      if ($m === 'Oct') $m = 'Okt';
      if ($m === 'Dec') $m = 'Des';
      
      if (array_key_exists($m, $monthCounts)) {
          $monthCounts[$m] = (int)$stat['count'];
      }
  }

  $monthlyLabelsJson = json_encode(array_keys($monthCounts));
  $monthlyDataJson = json_encode(array_values($monthCounts));

  $catLabels = [];
  $catCounts = [];
  foreach ($category_stats as $c) {
      $catLabels[] = $c['category'];
      $catCounts[] = (int)$c['count'];
  }
  $catLabelsJson = json_encode($catLabels);
  $catDataJson = json_encode($catCounts);
?>

<script>
  lucide.createIcons();

  // Booking Trend Chart
  const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
  new Chart(bookingsCtx, {
    type: 'line',
    data: {
      labels: <?= $monthlyLabelsJson ?>,
      datasets: [{
        label: 'Jumlah Pemesanan',
        data: <?= $monthlyDataJson ?>,
        borderColor: '#39ff78',
        backgroundColor: 'rgba(57, 255, 120, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#39ff78',
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { color: 'rgba(232, 240, 235, 0.6)' }
        },
        y: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { 
            color: 'rgba(232, 240, 235, 0.6)',
            stepSize: 1,
            precision: 0
          }
        }
      }
    }
  });

  // Category Distribution Chart
  const categoryCtx = document.getElementById('categoryChart').getContext('2d');
  new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
      labels: <?= $catLabelsJson ?>,
      datasets: [{
        data: <?= $catDataJson ?>,
        backgroundColor: [
          '#10b981', // Gunung (Emerald)
          '#0ea5e9', // Pantai (Ocean Blue)
          '#f59e0b', // Air Terjun (Amber)
          '#a855f7'  // Kota (Purple)
        ],
        borderWidth: 1,
        borderColor: 'rgba(12,18,14,0.9)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: 'rgba(232, 240, 235, 0.8)',
            padding: 15,
            font: { size: 12 }
          }
        }
      },
      cutout: '65%'
    }
  });
</script>

</body>
</html>
