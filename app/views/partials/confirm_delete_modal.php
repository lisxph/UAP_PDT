<div class="logout-modal" id="confirmDeleteModal" aria-hidden="true">
  <div class="logout-modal-backdrop" data-confirm-cancel></div>

  <div class="logout-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmDeleteTitle">
    <h3 id="confirmDeleteTitle">Hapus Ulasan?</h3>
    <p>Apakah Anda yakin ingin menghapus ulasan ini? Tindakan ini akan menghitung ulang akumulasi rating destinasi tersebut.</p>

    <div class="logout-actions">
      <button type="button" class="logout-confirm" id="confirmDeleteButton">Hapus</button>
      <button type="button" class="logout-cancel" data-confirm-cancel>Batal</button>
    </div>
  </div>
</div>

<script>
  (() => {
    const modal = document.getElementById('confirmDeleteModal');
    const deleteButtons = document.querySelectorAll('[data-delete-review]');
    const cancelButtons = modal.querySelectorAll('[data-confirm-cancel]');
    const confirmButton = document.getElementById('confirmDeleteButton');
    const backdrop = modal.querySelector('.logout-modal-backdrop');
    let currentReviewId = null;

    function openModal(id) {
      currentReviewId = id;
      modal?.classList.add('is-open');
      modal?.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
      currentReviewId = null;
      modal?.classList.remove('is-open');
      modal?.setAttribute('aria-hidden', 'true');
    }

    deleteButtons.forEach((btn) => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const id = this.dataset.reviewId;
        openModal(id);
      });
    });

    cancelButtons.forEach((b) => b.addEventListener('click', closeModal));
    backdrop?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeModal();
    });

    confirmButton.addEventListener('click', function() {
      if (!currentReviewId) return;
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/wandee/admin/review_delete';
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'review_id';
      input.value = currentReviewId;
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    });
  })();
</script>
