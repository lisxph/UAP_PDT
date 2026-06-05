<div class="logout-modal" id="logoutModal" aria-hidden="true">
  <div class="logout-modal-backdrop" data-logout-cancel></div>

  <div class="logout-dialog" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
    <h3 id="logoutTitle">Apakah anda ingin keluar?</h3>
    <p>Sesi akun akan ditutup dan anda perlu masuk kembali untuk melanjutkan.</p>

    <div class="logout-actions">
      <a href="/wandee/auth/logout" class="logout-confirm">Ya</a>
      <button type="button" class="logout-cancel" data-logout-cancel>Kembali</button>
    </div>
  </div>
</div>

<script>
  (() => {
    const logoutModal = document.getElementById('logoutModal');
    const logoutLinks = document.querySelectorAll('[data-logout-link]');
    const logoutCancelButtons = document.querySelectorAll('[data-logout-cancel]');

    function openLogoutModal(event) {
      event.preventDefault();
      logoutModal?.classList.add('is-open');
      logoutModal?.setAttribute('aria-hidden', 'false');
    }

    function closeLogoutModal() {
      logoutModal?.classList.remove('is-open');
      logoutModal?.setAttribute('aria-hidden', 'true');
    }

    logoutLinks.forEach((link) => {
      link.addEventListener('click', openLogoutModal);
    });

    logoutCancelButtons.forEach((button) => {
      button.addEventListener('click', closeLogoutModal);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeLogoutModal();
    });
  })();
</script>
