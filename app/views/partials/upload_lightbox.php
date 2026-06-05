<div class="lightbox-modal" id="uploadImageLightbox" aria-hidden="true" role="dialog" aria-label="Preview gambar upload">
  <button type="button" class="lightbox-close" id="uploadImageLightboxClose" aria-label="Tutup preview">&times;</button>
  <img src="" alt="Preview gambar upload" class="lightbox-content" id="uploadImageLightboxImage">
</div>

<script>
(function(){
  const modal = document.getElementById('uploadImageLightbox');
  const image = document.getElementById('uploadImageLightboxImage');
  const closeButton = document.getElementById('uploadImageLightboxClose');

  if (!modal || !image || !closeButton) {
    return;
  }

  function isUploadedImage(src) {
    return typeof src === 'string' && /\/wandee\/public\/uploads\/.+\.(jpe?g|png|gif|webp)$/i.test(src);
  }

  function openUploadImageLightbox(src, alt) {
    image.src = src;
    image.alt = alt || 'Preview gambar upload';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeUploadImageLightbox() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    image.src = '';
  }

  document.addEventListener('click', function(event) {
    const uploadLink = event.target.closest('a[href]');
    if (uploadLink && isUploadedImage(uploadLink.href)) {
      event.preventDefault();
      openUploadImageLightbox(uploadLink.href, uploadLink.querySelector('img')?.alt);
      return;
    }

    const uploadImage = event.target.closest('img');
    if (uploadImage && isUploadedImage(uploadImage.src)) {
      event.preventDefault();
      openUploadImageLightbox(uploadImage.src, uploadImage.alt);
    }
  });

  document.querySelectorAll('img').forEach(function(img) {
    if (isUploadedImage(img.src)) {
      img.classList.add('upload-lightbox-ready');
    }
  });

  closeButton.addEventListener('click', closeUploadImageLightbox);
  modal.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeUploadImageLightbox();
    }
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      closeUploadImageLightbox();
    }
  });
})();
</script>
