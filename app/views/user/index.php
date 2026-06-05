<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Dasbor Pengguna - Wandee</title>

  <!-- CSS -->
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">

<!-- FONT -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">

  <!-- LUCIDE -->
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="dashboard-page">
  <?php require __DIR__ . '/../partials/navbar.php'; ?>


  <!-- ===================================== -->
  <!-- HERO SECTION -->
  <!-- ===================================== -->

  <section class="hero dashboard-hero">

    <div class="hero-bg">
      <img src="/wandee/public/assets/img/gunung.png" alt="Pemandangan pegunungan" class="hero-img">
      <div class="hero-overlay"></div>
    </div>

    <div class="hero-content dashboard-hero-content">

      <p class="dashboard-welcome">
        Hai, <?php echo htmlspecialchars($name); ?>
      </p>

      <p class="hero-eyebrow">
        Jelajahi Keindahan Dunia Bersama
        <span class="accent">Wandee</span>
      </p>

      <h1 class="hero-title">
        Temukan destinasi eksotis,<br>
        petualangan tak terlupakan,<br>
        dan momen berharga.
      </h1>

      <div class="hero-cta">

        <a href="#destinations" class="btn-primary btn-lg">
          Mulai Eksplorasi
        </a>

        <a href="/wandee/user/riwayat" class="btn-ghost btn-lg">
          Lihat Perjalanan Saya
        </a>

      </div>

      <div class="search-bar">

        <div class="search-field search-destination">
          <input type="text" placeholder="Mau pergi ke mana?">
        </div>

        <div class="search-divider"></div>

        <button class="btn-search" aria-label="Cari">
          <i data-lucide="search"></i>
        </button>

      </div>

    </div>

  </section>

  <!-- ===================================== -->
  <!-- CATEGORIES -->
  <!-- ===================================== -->

  <section class="categories">

    <div class="container">

      <div class="cat-grid">

        <a href="#destinations" class="cat-card" data-cat="Gunung">
          <div class="cat-icon">
            <i data-lucide="mountain"></i>
          </div>
          <span>Gunung</span>
        </a>

        <a href="#destinations" class="cat-card" data-cat="Pantai">
          <div class="cat-icon">
            <i data-lucide="waves"></i>
          </div>
          <span>Pantai</span>
        </a>

        <a href="#destinations" class="cat-card" data-cat="Air Terjun">
          <div class="cat-icon">
            <i data-lucide="droplets"></i>
          </div>
          <span>Air Terjun</span>
        </a>

        <a href="#destinations" class="cat-card" data-cat="Kota">
          <div class="cat-icon">
            <i data-lucide="building-2"></i>
          </div>
          <span>Kota</span>
        </a>

      </div>

    </div>

  </section>

  <!-- ===================================== -->
  <!-- DESTINATIONS -->
  <!-- ===================================== -->

  <section class="recommended" id="destinations">

    <div class="container">

      <div class="section-header">

        <div>
          <h2 class="section-title">Rekomendasi Untukmu</h2>
          <p class="section-sub">
            Pilihan trip terbaik yang siap menemani perjalananmu.
          </p>
        </div>

        <a href="/wandee/user/favorite" class="view-all">
          Lihat Favorit
        </a>

      </div>

      <div class="dest-grid">

        <?php if (!empty($destinations)) : ?>
          <?php foreach ($destinations as $dest) : ?>
            <?php
              $destId = (int)$dest['id'];
              $displayRating = !empty($ratingStats[$destId]['total_reviews'])
                ? number_format((float)$ratingStats[$destId]['avg_rating'], 1)
                : '5.0';
              $isFavorite = in_array($destId, $favoriteDestinationIds ?? [], true);
            ?>

            <article class="dest-card" data-category="<?= htmlspecialchars($dest['category']) ?>">

              <div class="dest-thumb">
                <a href="/wandee/user/detail?id=<?= $destId ?>" class="dest-thumb-link" aria-label="Lihat detail <?= htmlspecialchars($dest['title']) ?>">
                  <img src="/wandee/public/assets/img/<?= htmlspecialchars($dest['image']) ?>"
                     alt="<?= htmlspecialchars($dest['title']) ?>"
                     class="dest-img"
                     onerror="this.src='/wandee/public/assets/img/gunung.png'" />
                </a>

                <div class="dest-badge">★ <?= htmlspecialchars($displayRating) ?></div>

                <form action="/wandee/user/favorite_toggle" method="POST" class="dest-favorite-form">
                  <input type="hidden" name="destination_id" value="<?= $destId ?>">
                  <button type="submit" class="dest-favorite-btn <?= $isFavorite ? 'is-active' : '' ?>" aria-label="<?= $isFavorite ? 'Hapus dari favorit' : 'Tambah ke favorit' ?>">
                    <i data-lucide="heart"></i>
                  </button>
                </form>

                <div class="dest-tags">
                  <span class="tag">Perjalanan Terbuka</span>
                </div>

              </div>

              <div class="dest-body">

                <h3 class="dest-name">
                  <a href="/wandee/user/detail?id=<?= $destId ?>"><?= htmlspecialchars($dest['title']) ?></a>
                </h3>

                <div class="dest-loc">
                  <i data-lucide="map-pin"></i>
                  <?= htmlspecialchars($dest['location']) ?>
                </div>

                <div class="dest-footer">

                  <div class="dest-date">
                    <?= $dest['trip_date'] ? htmlspecialchars($dest['trip_date']) : '-' ?>
                  </div>

                  <div class="dest-price">
                    <span class="per-person">Per Orang</span>
                    <span class="price"><?= htmlspecialchars($dest['price']) ?></span>
                  </div>

                </div>

              </div>

            </article>

          <?php endforeach; ?>
        <?php else : ?>
          <div class="dest-empty">Belum ada destinasi tersedia.</div>
        <?php endif; ?>

      </div>

    </div>

  </section>
  <?php require __DIR__ . '/../partials/footer.php'; ?>

  <!-- JS -->
  <script src="/wandee/public/assets/js/script.js"></script>

  <script>
    const catCards = document.querySelectorAll('.cat-card');
    let activeCategory = null;

    catCards.forEach((catCard) => {
      catCard.addEventListener('click', () => {
        const category = catCard.dataset.cat.toLowerCase();

        if (activeCategory === category) {
          activeCategory = null;
          catCards.forEach(c => c.classList.remove('active'));
        } else {
          activeCategory = category;
          catCards.forEach(c => c.classList.remove('active'));
          catCard.classList.add('active');
        }

        applyAllFilters();
        sortVisibleDestinationCards();
        document.getElementById('destinations').scrollIntoView({ behavior: 'smooth' });
      });
    });

    function applyAllFilters() {
      const query = document.querySelector('.search-destination input')?.value.trim().toLowerCase() || '';
      const queryWords = query.split(/\s+/).filter(Boolean);
      const destCards = document.querySelectorAll('.dest-card');
      const destGrid = document.querySelector('.dest-grid');
      let visible = 0;

      destCards.forEach((card) => {
        const name = card.querySelector('.dest-name')?.textContent.toLowerCase() || '';
        const loc = card.querySelector('.dest-loc')?.textContent.toLowerCase() || '';
        const cardCat = card.dataset.category?.toLowerCase() || '';
        const searchText = `${name} ${loc} ${cardCat}`;

        const hasQuery = !queryWords.length || queryWords.every((word) => searchText.includes(word));
        const hasCat = !activeCategory || cardCat === activeCategory;

        if (hasQuery && hasCat) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });

      let empty = destGrid?.querySelector('.dest-empty');
      if (visible === 0) {
        if (!empty) {
          empty = document.createElement('div');
          empty.className = 'dest-empty';
          empty.textContent = 'Tidak ada hasil yang sesuai';
          destGrid?.appendChild(empty);
        }
      } else {
        if (empty) empty.remove();
      }
    }

    const searchDestInput = document.querySelector('.search-destination input');
    const searchDateInputEl = document.getElementById('searchDateInput');
    const searchBtn = document.querySelector('.btn-search');

    if (searchDestInput) searchDestInput.addEventListener('input', applyAllFilters);
    if (searchDateInputEl) searchDateInputEl.addEventListener('change', applyAllFilters);
    if (searchBtn) searchBtn.addEventListener('click', () => {
      applyAllFilters();
      sortVisibleDestinationCards();
      document.getElementById('destinations')?.scrollIntoView({ behavior: 'smooth' });
    });
  </script>

</body>
</html>
