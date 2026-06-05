<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Favorit - Wandee</title>
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="favorite-page">
  <?php require __DIR__ . '/../partials/navbar.php'; ?>

  <main class="favorite-wrap">

    <div class="favorite-hero">
      <span class="favorite-kicker">
        <i data-lucide="heart"></i>
        Favorit Saya
      </span>
      <h1>Destinasi Favorit</h1>
      <p>Kelola destinasi favorit dan simpan perjalanan impian Anda.</p>
    </div>

    <?php if(empty($favorites)): ?>
      <div class="favorite-add">
        <div style="text-align:center;">
          <p style="margin-bottom:16px;">Anda belum menambahkan destinasi ke favorit.</p>
          <a href="/wandee/#destinations" class="btn-primary">Telusuri Destinasi</a>
        </div>
      </div>
    <?php else: ?>
      <div class="favorite-grid">
        <?php foreach($favorites as $favorite): ?>
          <article class="favorite-card">

            <div class="favorite-thumb">
              <img
                src="/wandee/public/assets/img/<?= htmlspecialchars($favorite['destination_image'] ?? '') ?>"
                alt="<?= htmlspecialchars($favorite['destination_title'] ?? '') ?>"
                onerror="this.src='/wandee/public/assets/img/gunung.png'">
              <span class="favorite-country">
                <?= htmlspecialchars($favorite['destination_location'] ?? '') ?>
              </span>
            </div>

            <div class="favorite-body">
              <h3><?= htmlspecialchars($favorite['destination_title'] ?? '') ?></h3>
              <p><?= htmlspecialchars($favorite['destination_location'] ?? '') ?></p>
              <div class="favorite-price">
                <strong><?= htmlspecialchars($favorite['destination_price'] ?? '') ?></strong>
                <a href="/wandee/user/detail?id=<?= (int)$favorite['destination_id'] ?>" class="favorite-detail">
                  Lihat Detail
                  <i data-lucide="arrow-right"></i>
                </a>
              </div>
              <form action="/wandee/user/favorite_toggle" method="POST" style="margin-top:16px;">
                <input type="hidden" name="destination_id" value="<?= (int)$favorite['destination_id'] ?>">
                <input type="hidden" name="return_to" value="/user/favorite">
                <button type="submit" class="btn-ghost" style="width:100%;">Hapus Favorit</button>
              </form>
            </div>

          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>

  <?php require __DIR__ . '/../partials/footer.php'; ?>
  <script src="/wandee/public/assets/js/script.js"></script>
  <script>lucide.createIcons();</script>
</body>
</html>