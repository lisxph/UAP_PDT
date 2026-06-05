<?php
$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
$success = $_SESSION['success'] ?? null;

unset(
    $_SESSION['form_errors'],
    $_SESSION['form_old'],
    $_SESSION['success']
);
?>

<!DOCTYPE html>
<html lang="id">

<head>

  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

  <title>Manage Destinasi - Wandee Admin</title>

  <link rel="stylesheet" href="/wandee/public/assets/css/styles.css?v=<?= time() ?>">

  <script src="https://unpkg.com/lucide@latest"></script>

</head>

<body class="admin-page">

<div class="admin-wrap">

  <?php require __DIR__ . '/../partials/admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <?php if($success): ?>
        <div class="success-alert">
           <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

      <div class="admin-title">

        <h1>
          Manage Destinasi
        </h1>

        <p>
          Kelola seluruh destinasi wisata Wandee dari dashboard admin.
        </p>

      </div>

      <div class="admin-user">

        <div class="admin-user-empty">
          <i data-lucide="user"></i>
        </div>

        <div>

          <strong>
            <?php echo $user['name']; ?>
          </strong>

          <p>
            <?php echo $user['email']; ?>
          </p>

        </div>

      </div>

    </div>

    <form
      action="/wandee/destination/add"
      method="POST"
      enctype="multipart/form-data"
      class="manage-form"
    >

      <input type="hidden" name="action" value="add">

      <div class="manage-grid">

        <?php if(!empty($errors)) : ?>
          <div class="form-alert">
            <strong>Periksa kembali data form:</strong>
            <ul>
              <?php foreach($errors as $error) : ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="manage-field">
          <label>Nama Destinasi</label>
          <input type="text" name="title" required value="<?= htmlspecialchars($old['title'] ?? '') ?>">
        </div>

        <div class="manage-field">
          <label>Lokasi</label>
          <input type="text" name="location" required value="<?= htmlspecialchars($old['location'] ?? '') ?>">
        </div>

        <div class="manage-field">

          <label>Kategori</label>

    <input
        type="text"
        name="category"
        list="category-list"
        placeholder="Pilih atau ketik kategori baru"
        value="<?= htmlspecialchars($old['category'] ?? '') ?>"
        required
    >

    <datalist id="category-list">
        <option value="Gunung">
        <option value="Pantai">
        <option value="Air Terjun">
        <option value="Kota">
    </datalist>

</div>

        <div class="manage-field">
          <label>Harga</label>
          <input type="number" name="price" required min="0" step="0.01" inputmode="decimal" value="<?= htmlspecialchars($old['price'] ?? '') ?>">
          <span class="field-help">Masukkan angka. Contoh: 1500000 atau 1500000.00</span>
        </div>

        <div class="manage-field full-span">

          <label>Deskripsi</label>

          <textarea
            name="description"
            placeholder="Masukkan deskripsi destinasi..."
          ></textarea>

        </div>

        <div class="manage-field full-span">

          <label>Upload Gambar</label>

          <label class="file-upload-box" for="destinationImage">
            <span class="file-upload-button">Choose File</span>
            <span class="file-upload-name" id="destinationImageName">No file chosen</span>
          </label>

          <input
            type="file"
            name="image"
            id="destinationImage"
            class="file-upload-input"
            accept=".jpg,.jpeg,.png"
            required
          >

          <span class="field-help">Format JPG / JPEG / PNG, maksimal 5 MB.</span>

        </div>

      </div>

      <button
        type="submit"
        class="btn-primary"
      >

        Tambah Destinasi

      </button>

    </form>

    <table class="manage-table">

      <thead>

        <tr>
          <th>Gambar</th>
          <th>Nama</th>
          <th>Kategori</th>
          <th>Lokasi</th>
          <th>Harga</th>
          <th>Aksi</th>
        </tr>

      </thead>

      <tbody>

      <?php foreach($destinations as $row) : ?>

        <tr>

          <td>

            <img
              src="/wandee/public/assets/img/<?php echo $row['image']; ?>"
              class="manage-thumb"
            >

          </td>

          <td>
            <?php echo $row['title']; ?>
          </td>

          <td>
            <?php echo $row['category']; ?>
          </td>

          <td>
            <?php echo $row['location']; ?>
          </td>

          <td>
            <?php echo $row['price']; ?>
          </td>

          <td>

            <div class="manage-actions">

              <a
                href="/wandee/admin/edit_destination?id=<?php echo $row['id']; ?>"
                class="btn-edit"
              >
                Edit
              </a>

              <a
                href="#"
                 class="btn-delete"
                 data-delete-id="<?php echo $row['id']; ?>"
              >
                Delete
              </a>

            </div>

          </td>

        </tr>

      <?php endforeach; ?>

      </tbody>

    </table>

  </main>

</div>

<?php require __DIR__ . '/../partials/logout_modal.php'; ?>
<div class="logout-modal" id="deleteModal" aria-hidden="true">
  <div class="logout-modal-backdrop" data-delete-cancel></div>

  <div class="logout-dialog" role="dialog" aria-modal="true">
    <h3>Apakah anda yakin ingin menghapus destinasi ini?</h3>

    <p>
      Data destinasi yang dihapus tidak dapat dikembalikan kembali.
    </p>

    <div class="logout-actions">
      <a href="#" id="deleteConfirmBtn" class="logout-confirm">
        Ya
      </a>

      <button
        type="button"
        class="logout-cancel"
        data-delete-cancel>
        Kembali
      </button>
    </div>
  </div>
</div>

<script>
  const destinationImage = document.getElementById('destinationImage');
  const destinationImageName = document.getElementById('destinationImageName');

  if (destinationImage && destinationImageName) {
  destinationImage.addEventListener('change', () => {
    destinationImageName.textContent = destinationImage.files[0]?.name || 'No file chosen';
  });
}

const deleteModal = document.getElementById('deleteModal');
const deleteButtons = document.querySelectorAll('[data-delete-id]');
const deleteCancelButtons = document.querySelectorAll('[data-delete-cancel]');
const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');

let deleteUrl = '';

function openDeleteModal(url)
{
  deleteUrl = url;

  deleteModal?.classList.add('is-open');
  deleteModal?.setAttribute('aria-hidden', 'false');
}

function closeDeleteModal()
{
  deleteModal?.classList.remove('is-open');
  deleteModal?.setAttribute('aria-hidden', 'true');
}

deleteButtons.forEach((button) => {

  button.addEventListener('click', (event) => {

    event.preventDefault();

    const id = button.dataset.deleteId;

    openDeleteModal(
      '/wandee/destination/delete?id=' + id
    );

  });

});

deleteCancelButtons.forEach((button) => {

  button.addEventListener('click', closeDeleteModal);

});

deleteConfirmBtn?.addEventListener('click', (event) => {

  event.preventDefault();

  if(deleteUrl){
    window.location.href = deleteUrl;
  }

});

document.addEventListener('keydown', (event) => {

  if(event.key === 'Escape'){
    closeDeleteModal();
  }

});

lucide.createIcons();

</script>

</body>
</html>
