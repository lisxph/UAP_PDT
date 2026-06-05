# 🧭 Wandee (Proyek UAP)

Wandee adalah **platform pemesanan wisata** yang dibangun menggunakan PHP dan MySQL melalui Laragon. Sistem ini mengelola destinasi wisata, booking perjalanan, pembayaran, dan ulasan pengguna. Dalam konteks mata kuliah Pemrosesan Data Terdistribusi, Wandee dilengkapi dengan implementasi **trigger otomatis, fragmentasi data, backup database + task scheduler**, dan **replikasi Master-Master** untuk meningkatkan keandalan dan ketersediaan sistem.

---

## 🗂️ Detail Konsep

### ⚡ Trigger

Trigger diimplementasikan untuk mengotomasi proses bisnis pada Wandee tanpa perlu intervensi manual dari aplikasi. Terdapat 5 trigger yang dibuat beserta tabel log pendukungnya.

| Nama Trigger | Event | Tabel | Fungsi |
|---|---|---|---|
| `after_insert_review` | INSERT | reviews | Auto update rating destinasi berdasarkan AVG semua review |
| `after_delete_review` | DELETE | reviews | Recalculate rating destinasi, kembali ke 0.0 jika tidak ada review tersisa |
| `after_update_payment` | UPDATE | payments | Auto ubah status booking menjadi paid/cancelled + catat ke log |
| `after_delete_destinasi` | DELETE | destinations | Simpan data destinasi ke log sebelum benar-benar dihapus admin |
| `after_insert_booking` | INSERT | bookings | Catat setiap booking baru ke tabel log_booking_baru |

Tabel log yang dibuat sebagai audit trail:
- `log_rating_destinasi` — mencatat perubahan rating setiap destinasi
- `log_pembayaran` — mencatat perubahan status payment beserta alasan penolakan
- `log_hapus_destinasi` — menyimpan data destinasi sebelum dihapus admin
- `log_booking_baru` — mencatat setiap booking baru yang masuk

Contoh implementasi trigger `after_update_payment`:

```sql
DELIMITER //
CREATE TRIGGER after_update_payment
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
  -- Jika payment diverifikasi → booking otomatis paid
  IF NEW.payment_status = 'verified' AND OLD.payment_status != 'verified' THEN
    UPDATE bookings SET payment_status = 'paid'
    WHERE id = NEW.booking_id;
  END IF;

  -- Jika payment ditolak → booking otomatis cancelled
  IF NEW.payment_status = 'rejected' AND OLD.payment_status != 'rejected' THEN
    UPDATE bookings SET payment_status = 'cancelled'
    WHERE id = NEW.booking_id;
  END IF;

  -- Catat semua perubahan ke log
  IF NEW.payment_status != OLD.payment_status THEN
    INSERT INTO log_pembayaran
      (payment_id, booking_id, nama_user, status_lama, status_baru, alasan_tolak)
    VALUES
      (NEW.id, NEW.booking_id, v_nama_user, OLD.payment_status, NEW.payment_status, NEW.rejection_reason);
  END IF;
END;
//
DELIMITER ;
```

---

### 🧩 Fragmentasi Data

Fragmentasi diterapkan menggunakan **VIEW** agar data tetap aman dan bisa direkonstruksi kapan saja. Tiga jenis fragmentasi diimplementasikan pada database Wandee.

**1. Fragmentasi Horizontal — Destinations per Kategori**

Tabel `destinations` dipecah berdasarkan kolom `category` menjadi 4 fragmen: Gunung, Pantai, Air Terjun, dan Kota.

```sql
CREATE OR REPLACE VIEW frag_dest_gunung AS
SELECT * FROM destinations WHERE category = 'Gunung';

CREATE OR REPLACE VIEW frag_dest_pantai AS
SELECT * FROM destinations WHERE category = 'Pantai';
```

**2. Fragmentasi Vertikal — Data Publik vs Detail**

Kolom `destinations` dipisah menjadi data publik (tampil di halaman listing) dan data detail (halaman detail destinasi).

```sql
-- Fragmen publik: kolom yang tampil di card listing
CREATE OR REPLACE VIEW frag_dest_publik AS
SELECT id, title, location, category, image, trip_date, rating
FROM destinations;

-- Fragmen detail: kolom yang tampil di halaman detail
CREATE OR REPLACE VIEW frag_dest_detail AS
SELECT id, title, price, description, created_at
FROM destinations;
```

**3. Fragmentasi Campuran — Destinasi Populer**

Kombinasi filter baris dan kolom: destinasi Pantai & Gunung dengan rating ≥ 4.5 beserta jumlah booking.

```sql
CREATE OR REPLACE VIEW frag_dest_populer AS
SELECT
  d.id, d.title, d.location, d.category,
  d.rating,
  COUNT(b.id) AS total_booking
FROM destinations d
LEFT JOIN bookings b ON b.destination_id = d.id
WHERE d.category IN ('Pantai', 'Gunung')
  AND d.rating >= 4.5
GROUP BY d.id
ORDER BY d.rating DESC;
```

> **Reconstruction Rule** — Semua fragmen horizontal dapat digabungkan kembali menggunakan `UNION ALL`, sedangkan fragmen vertikal menggunakan `JOIN` pada kolom `id`, untuk menghasilkan data original yang lengkap.

---

### 💾 Backup Otomatis

Sistem backup otomatis menggunakan **mysqldump** via script `.bat` yang dijadwalkan oleh **Windows Task Scheduler**. Backup berjalan setiap hari secara otomatis dan menyimpan file dengan timestamp.

**Alur backup:**
```
Task Scheduler → wandee_backup.bat → mysqldump → D:\Backup\wandee\
```

Script `wandee_backup.bat`:

```bat
set "backupDir=D:\Backup\wandee"
set "mysqlDir=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin"
set "dbName=wandee"

:: Generate timestamp & jalankan backup
"%mysqlDir%\mysqldump.exe" -u root --no-tablespaces %dbName% > "%backupDir%\wandee_backup_%timestamp%.sql"

:: Rotasi otomatis — hapus backup lebih dari 7 hari
forfiles /p "%backupDir%" /m *.sql /d -7 /c "cmd /c del @path"
```

Fitur backup:
- File backup diberi nama dengan timestamp otomatis, contoh: `wandee_backup_2026-06-05_07-00.sql`
- Log sukses/gagal dicatat ke `backup_log.txt`
- File backup lebih dari 7 hari dihapus otomatis untuk menghemat penyimpanan
- Task Scheduler menjalankan backup setiap hari secara otomatis

---

### 🔄 Replikasi Master-Master

Replikasi Master-Master memungkinkan dua server MySQL saling menyinkronkan data secara dua arah. Kedua server dapat menerima operasi baca dan tulis, sehingga sistem tetap beroperasi meski salah satu server mengalami gangguan.

```
🖥️ Master 1 (server-id=1)  ⇄  🖥️ Master 2 (server-id=2)
     172.20.10.6                    172.20.10.2
          ↕ Asynchronous Replication ↕
```

Konfigurasi slave di Master 1 (MySQL 8.0.30):

```sql
CHANGE MASTER TO
MASTER_HOST='172.20.10.2',
MASTER_USER='replica',
MASTER_PASSWORD='password',
MASTER_LOG_FILE='mysql-bin.000001',
MASTER_LOG_POS=886;

START SLAVE;
SHOW SLAVE STATUS;
```
Uji coba replikasi dari Master 2
```sql
INSERT INTO users (name, email, password, role)
VALUES ('Alyssa', 'alyssa@gmail.com', '123456', 'user');
```
![Hasil di master 1](https://github.com/user-attachments/assets/70a96477-d414-49a0-bce2-ecc28a370501)

---

## 🛠️ Teknologi

- **Backend** — PHP
- **Database** — MySQL 8.0.30
- **Server Lokal** — Laragon
- **Backup Tool** — mysqldump + Windows Task Scheduler
