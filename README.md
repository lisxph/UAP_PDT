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

### 1. Contoh implementasi trigger `after_insert_review`:
```sql
DELIMITER //
CREATE TRIGGER `after_insert_review`
AFTER INSERT ON `reviews`
FOR EACH ROW
BEGIN
    DECLARE v_avg_rating  DECIMAL(2,1);
    DECLARE v_total       INT;
    DECLARE v_rating_lama DECIMAL(2,1);
    DECLARE v_judul       VARCHAR(255);

    -- Ambil rating lama dan judul destinasi
    SELECT rating, title
    INTO v_rating_lama, v_judul
    FROM destinations
    WHERE id = NEW.destination_id;

    -- Hitung rata-rata rating baru dari semua review
    SELECT ROUND(AVG(rating), 1), COUNT(*)
    INTO v_avg_rating, v_total
    FROM reviews
    WHERE destination_id = NEW.destination_id;

    -- Update rating di tabel destinations
    UPDATE destinations
    SET rating = v_avg_rating
    WHERE id = NEW.destination_id;

    -- Catat perubahan ke log
    INSERT INTO log_rating_destinasi
        (destination_id, judul_destinasi, rating_lama, rating_baru, total_review, keterangan)
    VALUES
        (NEW.destination_id, v_judul, v_rating_lama, v_avg_rating, v_total, 'Update dari review baru');
END;
//
DELIMITER ;
```
### 2. Contoh implementasi trigger `after_delete_review`:
```sql
DELIMITER //
CREATE TRIGGER `after_delete_review`
AFTER DELETE ON `reviews`
FOR EACH ROW
BEGIN
    DECLARE v_avg_rating  DECIMAL(2,1);
    DECLARE v_total       INT;
    DECLARE v_rating_lama DECIMAL(2,1);
    DECLARE v_judul       VARCHAR(255);

 -- Ambil rating lama dan judul destinasi
    SELECT rating, title
    INTO v_rating_lama, v_judul
    FROM destinations
    WHERE id = OLD.destination_id;

-- Hitung ulang rata-rata setelah review dihapus
    SELECT ROUND(AVG(rating), 1), COUNT(*)
    INTO v_avg_rating, v_total
    FROM reviews
    WHERE destination_id = OLD.destination_id;

 -- Jika tidak ada review tersisa, kembalikan rating ke 5.0
    IF v_total = 0 THEN
        SET v_avg_rating = 0.0;
    END IF;

    UPDATE destinations
    SET rating = v_avg_rating
    WHERE id = OLD.destination_id;

    INSERT INTO log_rating_destinasi
        (destination_id, judul_destinasi, rating_lama, rating_baru, total_review, keterangan)
    VALUES
        (OLD.destination_id, v_judul, v_rating_lama, v_avg_rating, v_total, 'Recalculate setelah review dihapus');
END;
//
DELIMITER ;
```
 
### 3. Contoh implementasi trigger `after_update_payment`:
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

### 4. Contoh implementasi trigger `after_delete_destinasi`:
``SQL
DELIMITER //
CREATE TRIGGER `after_delete_destinasi`
AFTER DELETE ON `destinations`
FOR EACH ROW
BEGIN
    INSERT INTO log_hapus_destinasi
        (dest_id, judul, lokasi, kategori, harga, rating)
    VALUES
        (OLD.id, OLD.title, OLD.location, OLD.category, OLD.price, OLD.rating);
END;
//
DELIMITER ;
```
### 5. Contoh implementasi trigger `after_insert_booking`:
``SQL
DROP TRIGGER IF EXISTS `after_insert_booking`;

DELIMITER //
CREATE TRIGGER `after_insert_booking`
AFTER INSERT ON `bookings`
FOR EACH ROW
BEGIN
    INSERT INTO log_booking_baru
        (booking_id, user_id, destination_id, total_people, total_price, keterangan)
    VALUES
        (NEW.id, NEW.user_id, NEW.destination_id, NEW.total_people, NEW.total_price, 'Booking baru masuk');
END;
//
DELIMITER ;
```

Cek Trigger yang Sudah Dibuat
https://raw.githubusercontent.com/TiwiMustikaDewi/LearnAndroidMobile/refs/heads/main/Screenshot%202026-06-05%20175216.png
---
## 🧩 Fragmentasi Data

Fragmentasi diimplementasikan menggunakan dua pendekatan pada database Wandee.

**Alur Fragmentasi:**
### 1. Fragmentasi Fisik — Bookings per Tahun (RANGE PARTITION)

Tabel `bookings` dipartisi secara fisik berdasarkan tahun dari kolom `created_at`, sehingga data booking terpisah di masing-masing partisi dan query lebih efisien.

```sql
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `destination_id` int DEFAULT NULL,
  `total_people` int DEFAULT '1',
  `total_price` int DEFAULT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `trip_status` enum('new','ongoing','completed','cancelled') DEFAULT 'new',
  `created_at` date NOT NULL DEFAULT (CURRENT_DATE),
  PRIMARY KEY (`id`, `created_at`)
) ENGINE=InnoDB
PARTITION BY RANGE COLUMNS(created_at) (
  PARTITION p2024 VALUES LESS THAN ('2025-01-01'),
  PARTITION p2025 VALUES LESS THAN ('2026-01-01'),
  PARTITION p2026 VALUES LESS THAN ('2027-01-01'),
  PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

Verifikasi partisi:
```sql
SELECT PARTITION_NAME, TABLE_ROWS
FROM information_schema.PARTITIONS
WHERE TABLE_NAME = 'bookings' AND TABLE_SCHEMA = 'wandee';
```

### 2. Fragmentasi Horizontal — Destinations per Kategori

Tabel `destinations` dipecah berdasarkan baris menggunakan kolom `category`.

```sql
CREATE TABLE frag_dest_gunung AS
SELECT * FROM destinations WHERE category = 'Gunung';

CREATE TABLE frag_dest_pantai AS
SELECT * FROM destinations WHERE category = 'Pantai';

CREATE TABLE frag_dest_lainnya AS
SELECT * FROM destinations WHERE category NOT IN ('Gunung', 'Pantai');
```

**Reconstruction Rule** — gabungkan kembali dengan UNION ALL:
```sql
SELECT * FROM frag_dest_gunung
UNION ALL
SELECT * FROM frag_dest_pantai
UNION ALL
SELECT * FROM frag_dest_lainnya;
```

### 3. Fragmentasi Vertikal — Data Publik vs Detail

Kolom `destinations` dipisah menjadi data publik dan data detail.

```sql
-- Fragmen publik (tampil di halaman listing)
CREATE TABLE frag_dest_publik AS
SELECT id, title, location, category, image, trip_date, rating
FROM destinations;

-- Fragmen detail (tampil di halaman detail destinasi)
CREATE TABLE frag_dest_detail AS
SELECT id, title, price, description, created_at
FROM destinations;
```

**Reconstruction Rule** — gabungkan kembali dengan JOIN:
```sql
SELECT p.*, d.price, d.description, d.created_at
FROM frag_dest_publik p
JOIN frag_dest_detail d ON p.id = d.id;
```

### 4. Fragmentasi Campuran — Destinasi Populer

Kombinasi filter baris dan kolom: destinasi Gunung & Pantai dengan rating ≥ 3.0.

```sql
CREATE TABLE frag_dest_populer AS
SELECT id, title, location, category, rating
FROM destinations
WHERE category IN ('Gunung', 'Pantai')
AND rating >= 3.0;
```

---

### 💾 Backup Otomatis

Sistem backup otomatis menggunakan **mysqldump** via script `.bat` yang dijadwalkan oleh **Windows Task Scheduler**. Backup berjalan setiap hari jam 20.11 secara otomatis dan menyimpan file dengan timestamp.

**Alur backup:**
```
Task Scheduler → wandee_backup.bat → mysqldump → D:\Backup\wandee\
```

Script `wandee_backup.bat`:

```bat
@echo off
setlocal enabledelayedexpansion

set "backupDir=D:\Backup\wandee"
set "mysqlDir=C:\laragonbaru\bin\mysql\mysql-8.0.30-winx64\bin"

:: Extract date parts
set "month=%date:~4,2%"
set "day=%date:~7,2%"
set "year=%date:~10,4%"

:: Extract time parts and ensure two-digit hour
set "hour=%time:~0,2%"
set "minute=%time:~3,2%"

:: Replace leading space with zero for hours less than 10
if "!hour:~0,1!"==" " set "hour=0!hour:~1,1!"

:: Construct timestamp
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set "timestamp=%datetime:~0,8%_%datetime:~8,6%"

:: Backup database
"%mysqlDir%\mysqldump.exe" -u root wandee > "%backupDir%\backup_%timestamp%.sql"

endlocal
exit /b 0
```
Hasil Backup 

![Hasil di master 1](https://github.com/user-attachments/assets/9513176d-a9ee-4a6e-bf44-b8bb95bad73e)
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
Hasil di Master 1
![Hasil di master 1](https://github.com/user-attachments/assets/70a96477-d414-49a0-bce2-ecc28a370501)

---

## 🛠️ Teknologi

- **Backend** — PHP
- **Database** — MySQL 8.0.30
- **Server Lokal** — Laragon
- **Backup Tool** — mysqldump + Windows Task Scheduler
