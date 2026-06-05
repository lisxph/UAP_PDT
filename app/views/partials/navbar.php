<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? ''), PHP_URL_PATH);

function nav_active($needle, $currentPath){
    return strpos($currentPath, $needle) !== false ? ' class="active"' : '';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wandee</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<nav class="navbar">

  <a href="/wandee/" class="nav-logo">Wandee</a>

  <ul class="nav-links">

    <li>
      <a href="/wandee/"<?php echo (strpos($currentPath, '/user/ulasan') === false && strpos($currentPath, '/user/riwayat') === false && strpos($currentPath, '/user/favorite') === false && strpos($currentPath, '/user/profile') === false) ? ' class="active"' : ''; ?>>
        Jelajah
      </a>
    </li>

    <li>
      <a href="/wandee/#destinations">
        Destinasi
      </a>
    </li>

    <li>
      <a href="/wandee/user/ulasan"<?php echo nav_active('/user/ulasan', $currentPath); ?>>
        Ulasan
      </a>
    </li>

    <li>
      <a href="/wandee/user/riwayat"<?php echo nav_active('/user/riwayat', $currentPath); ?>>
        Perjalanan Saya
      </a>
    </li>

    <li>
      <a href="/wandee/user/favorite"<?php echo nav_active('/user/favorite', $currentPath); ?>>
        Favorit
      </a>
    </li>

  </ul>

  <div class="nav-actions">

    <?php if(isset($_SESSION['user_id'])) : ?>

      <a href="/wandee/user/profile" class="btn-icon" aria-label="Profil">
        <i data-lucide="user"></i>
      </a>

      <a href="/wandee/#destinations" class="btn-primary">
        Pesan Sekarang
      </a>

    <?php else : ?>

      <a href="/wandee/auth/loginregister" class="btn-icon" aria-label="Profil">
        <i data-lucide="user"></i>
      </a>

      <a href="/wandee/auth/loginregister" class="btn-primary">
        Pesan Sekarang
      </a>

    <?php endif; ?>

  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const navLinks = document.querySelectorAll(".nav-links a");
      
      function checkActiveHash() {
        const hash = window.location.hash;
        const pathname = window.location.pathname;
        const isHome = pathname === "/wandee/" || pathname.endsWith("/user/index") || pathname.endsWith("/public/index.php") || pathname === "/wandee/public/";

        if (isHome) {
          navLinks.forEach(link => {
            const href = link.getAttribute("href");
            if (hash === "#destinations") {
              if (href.includes("#destinations")) {
                link.classList.add("active");
              } else {
                link.classList.remove("active");
              }
            } else {
              if (href.includes("#destinations")) {
                link.classList.remove("active");
              } else if (href === "/wandee/" || href.includes("user/index")) {
                if (link.textContent.trim() === "Jelajah") {
                  link.classList.add("active");
                }
              }
            }
          });
        }
      }

      // Check on load
      checkActiveHash();

      // Check on hashchange
      window.addEventListener("hashchange", checkActiveHash);

      // Handle clicking Destinasi directly
      navLinks.forEach(link => {
        link.addEventListener("click", function () {
          const href = this.getAttribute("href");
          if (href.includes("#destinations")) {
            navLinks.forEach(l => l.classList.remove("active"));
            this.classList.add("active");
          }
        });
      });
    });
  </script>

</nav>