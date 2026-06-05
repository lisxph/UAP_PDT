<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? ''), PHP_URL_PATH);
function admin_nav_active($route, $currentPath){
    $path = rtrim($currentPath, '/');
    return (substr($path, -strlen($route)) === $route);
}
?>
<aside class="admin-sidebar">

  <div class="admin-logo">
    Wandee Admin
  </div>

  <nav class="admin-menu">

    <a href="/wandee/admin/dashboard"<?php echo admin_nav_active('/admin/dashboard', $currentPath) ? ' class="active"' : ''; ?>>
      <i data-lucide="layout-dashboard"></i>
      Dashboard
    </a>

    <a href="/wandee/admin/manage"<?php echo (admin_nav_active('/admin/manage', $currentPath) || admin_nav_active('/admin/edit_destination', $currentPath)) ? ' class="active"' : ''; ?>>
      <i data-lucide="map"></i>
      Manage Destinasi
    </a>

    <a href="/wandee/admin/manage_payments"<?php echo admin_nav_active('/admin/manage_payments', $currentPath) ? ' class="active"' : ''; ?>>
      <i data-lucide="credit-card"></i>
      Verifikasi Pembayaran
    </a>

    <a href="/wandee/admin/manage_reviews"<?php echo admin_nav_active('/admin/manage_reviews', $currentPath) ? ' class="active"' : ''; ?>>
      <i data-lucide="message-square"></i>
      Manage Ulasan
    </a>

    <a href="/wandee/admin/profile"<?php echo admin_nav_active('/admin/profile', $currentPath) ? ' class="active"' : ''; ?>>
      <i data-lucide="user"></i>
      Profil Saya
    </a>

    <a href="/wandee/auth/logout" data-logout-link>
      <i data-lucide="log-out"></i>
      Keluar
    </a>

  </nav>

</aside>

