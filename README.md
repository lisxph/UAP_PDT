# 🧭 Wandee (Proyek UAP)
Wandee adalah platform pemesanan wisata yang dibangun menggunakan PHP dan MySQL melalui Laragon. Sistem ini mengelola destinasi wisata, booking perjalanan, pembayaran, dan ulasan pengguna. Dalam konteks mata kuliah Pemrosesan Data Terdistribusi, Wandee dilengkapi dengan implementasi **Database Views, SQL Joins & Set Operations, Transactions, Deadlock Management, Function & Stored Procedure, trigger otomatis, fragmentasi data, backup database + task scheduler, dan replikasi Master-Master** untuk meningkatkan keandalan dan ketersediaan sistem.

_____

# 🗂️ Detail Konsep

## 🔗 SQL Joins & Set Operations

### 1. INNER JOIN — Booking Valid (DashboardModel::getValidBookings)
Menampilkan hanya booking yang dipastikan memiliki user dan destinasi yang valid. Tidak menampilkan data kosong.
```sql
SELECT b.id, b.total_people, b.total_price, b.payment_status,
       b.trip_status, b.created_at,
       u.name  AS user_name,  u.email AS user_email,
       d.title AS destination_title, d.location AS destination_location
FROM bookings b
INNER JOIN users u        ON b.user_id        = u.id
INNER JOIN destinations d ON b.destination_id = d.id
ORDER BY b.created_at DESC
LIMIT 10;
```
### 2. LEFT JOIN — Booking + User + Destinasi (DashboardModel::getRecentBookings)
Menampilkan booking terbaru beserta nama user dan judul destinasi. Booking tetap muncul walau data user atau destinasi tidak ditemukan.
```sql
SELECT b.*, u.name AS user_name, d.title AS destination_title
FROM bookings b
LEFT JOIN users u        ON b.user_id        = u.id
LEFT JOIN destinations d ON b.destination_id = d.id
ORDER BY b.created_at DESC
LIMIT 5;
```

### 3. RIGHT JOIN — Destinasi + Jumlah Booking (DestinationModel::getDestinationsWithBookingCount)
Menampilkan semua destinasi beserta jumlah booking-nya, termasuk destinasi yang belum pernah dibooking sama sekali (booking_count = 0).
```sql
SELECT d.id, d.title, d.location, d.category,
       d.price, d.rating,
       COUNT(b.id) AS booking_count
FROM bookings b
RIGHT JOIN destinations d ON b.destination_id = d.id
GROUP BY d.id, d.title, d.location, d.category, d.price, d.rating
ORDER BY booking_count DESC;
```

### 4. Simulasi FULL JOIN (DestinationModel::getFullJoinUsersDestinations)
FULL JOIN disimulasikan dengan LEFT JOIN + UNION + RIGHT JOIN. FULL JOIN menampilkan semua user dan semua destinasi, termasuk user yang belum booking dan destinasi yang belum pernah dibooking.
```sql
SELECT u.id AS user_id, u.name AS user_name,
       d.id AS dest_id, d.title AS dest_title,
       b.id AS booking_id, b.payment_status
FROM users u
LEFT JOIN bookings b     ON u.id = b.user_id
LEFT JOIN destinations d ON b.destination_id = d.id
UNION
SELECT u.id AS user_id, u.name AS user_name,
       d.id AS dest_id, d.title AS dest_title,
       b.id AS booking_id, b.payment_status
FROM users u
RIGHT JOIN bookings b     ON u.id = b.user_id
RIGHT JOIN destinations d ON b.destination_id = d.id
ORDER BY user_id ASC, dest_id ASC;
```

### 5. UNION — Rekonstruksi Fragmentasi (DestinationModel::getAllFromFragments)
Menggabungkan data dari tiga tabel fragmentasi menjadi satu daftar destinasi tanpa duplikat. UNION secara otomatis membuang data yang sama persis.
```sql
SELECT id, title, location, category, image, trip_date, price, rating
FROM frag_dest_gunung
UNION
SELECT id, title, location, category, image, trip_date, price, rating
FROM frag_dest_pantai
UNION
SELECT id, title, location, category, image, trip_date, price, rating
FROM frag_dest_lainnya
ORDER BY rating DESC;
```

### 6. UNION ALL — Activity Log Admin (DashboardModel::getActivityLog)
Menggabungkan semua log aktivitas dari tiga tabel log (booking baru, hapus destinasi, pembayaran) menjadi satu timeline. UNION ALL dipakai agar tidak ada log yang terbuang walau isinya mirip.
```sql
SELECT 'Booking Baru'    AS jenis, booking_id AS ref_id,
       keterangan, waktu
FROM log_booking_baru
UNION ALL
SELECT 'Hapus Destinasi' AS jenis, dest_id AS ref_id,
       CONCAT('Hapus: ', judul), dihapus_at
FROM log_hapus_destinasi
UNION ALL
SELECT 'Pembayaran'      AS jenis, payment_id AS ref_id,
       CONCAT(nama_user, ' - ', status_lama, ' → ', status_baru), waktu
FROM log_pembayaran
ORDER BY waktu DESC
LIMIT 20;
```
## 🔒 Transactions
Transaksi yang sesungguhnya ada di BookingModel.php di method createWithPayment(). Transaksi diimplementasikan untuk menjamin atomicity — kedua INSERT (ke tabel bookings dan payments) harus berhasil semua atau gagal semua. Tidak bisa booking masuk tapi payment-nya tidak, atau sebaliknya.
Implementasi di BookingModel::createWithPayment:
```sql
public function createWithPayment($bookingData, $paymentData) {
    mysqli_autocommit($this->conn, false);
    mysqli_begin_transaction($this->conn);  // ← START TRANSACTION

    try {
        // INSERT ke tabel bookings
        $r1 = mysqli_query($this->conn, $q1);
        if (!$r1) throw new Exception("Gagal membuat booking");

        $booking_id = mysqli_insert_id($this->conn);

        // INSERT ke tabel payments (menggunakan booking_id dari step 1)
        $r2 = mysqli_query($this->conn, $q2);
        if (!$r2) throw new Exception("Gagal membuat payment");

        mysqli_commit($this->conn);         // ← COMMIT (kalau sukses)
        mysqli_autocommit($this->conn, true);
        return ['booking_id' => $booking_id, 'payment_id' => mysqli_insert_id($this->conn)];

    } catch (Exception $e) {
        $errno = mysqli_errno($this->conn); // Simpan errno SEBELUM rollback karena rollback mereset errno ke 0
        mysqli_rollback($this->conn);       // ← ROLLBACK (kalau gagal)
        mysqli_autocommit($this->conn, true);
        return ['error' => $e->getMessage(), 'errno' => $errno];
    }
}
```

## 💥 Deadlock Management
Deadlock disimulasikan dan ditangani secara eksplisit di Wandee. Deadlock terjadi di method createWithPayment() pada BookingModel.php, tepatnya saat dua user menekan tombol "Lanjutkan ke Instruksi Pembayaran" pada destinasi yang sama secara bersamaan.

**Skenario Deadlock (Pola A-B Circular Wait):**
Device A (user 7):                    Device B (user 8):
START TRANSACTION                     START TRANSACTION
│                                     │
├─ LOCK destinations(id=5) ✓          ├─ LOCK payments(id=29) ✓
│                                     │
├─ SLEEP 8 detik...                   ├─ SLEEP 2 detik...
│                                     │
│                                     ├─ coba LOCK destinations(id=5)
│                                     │  ← DITAHAN, A masih pegang
│                                     │
├─ coba LOCK payments(id=29)          │
│  ← DITAHAN, B masih pegang          │
│                                     │
▼                                     ▼
         A nunggu B, B nunggu A
              → DEADLOCK!
         MySQL rollback salah satu

### Implementasi simulasi di BookingModel::createWithPayment:
``sql
if ($isOddUser) {
    // Urutan A: destinations → payments
    mysqli_query($this->conn, "SELECT id FROM destinations WHERE id = $destination_id FOR UPDATE");
    mysqli_query($this->conn, "SELECT SLEEP(8)");
    mysqli_query($this->conn, "SELECT id FROM payments WHERE id = 29 FOR UPDATE");
} else {
    // Urutan B: payments → destinations (urutan TERBALIK dari A)
    mysqli_query($this->conn, "SELECT id FROM payments WHERE id = 29 FOR UPDATE");
    mysqli_query($this->conn, "SELECT SLEEP(2)");
    mysqli_query($this->conn, "SELECT id FROM destinations WHERE id = $destination_id FOR UPDATE");
}
```

### Penanganan Deadlock
**1. Penanganan oleh DBMS (Otomatis)**
MySQL InnoDB mendeteksi deadlock secara otomatis dan melakukan ROLLBACK pada salah satu transaksi. Transaksi yang di-rollback mendapat error 1213 dan harus diulangi.

2. Rollback Manual:
ROLLBACK;           -- Batalkan semua perubahan dalam transaksi
SET autocommit = 1; -- Kembalikan autocommit ke normal

3. Penanganan di PHP — Deteksi errno 1213:
if (isset($result['error'])) {
    // MySQL errno 1213 = Deadlock found when trying to get lock
    if (($result['errno'] ?? 0) === 1213) {
        header('Location: /wandee/user/payment?id=' . $destination_id . '&error=deadlock');
    } else {
        header('Location: /wandee/user/payment?id=' . $destination_id . '&error=booking_failed');
    }
    exit;
}

Tampilan pesan di payment.php:
<?php if (($_GET['error'] ?? '') === 'deadlock'): ?>
    Terjadi Deadlock — Transaksi Dibatalkan Otomatis
<?php endif; ?>


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
        (OLD.destination_id, v_judul, v_rating_lama, v_avg_rating, v_total, 'Perhitungan ulang rating setelah ulasan dihapus');
END;
//
DELIMITER ;
```
### 3. Contoh implementasi trigger `after_update_payment`:
```sql
DELIMITER //
CREATE TRIGGER `after_update_payment`
AFTER UPDATE ON `payments`
FOR EACH ROW
BEGIN
    DECLARE v_nama_user VARCHAR(100);

    -- Ambil nama user dari booking
    SELECT u.name INTO v_nama_user
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.id = NEW.booking_id
    LIMIT 1;

    -- Jika status berubah ke 'verified'
    IF NEW.payment_status = 'verified' AND OLD.payment_status != 'verified' THEN
        UPDATE bookings
        SET payment_status = 'paid'
        WHERE id = NEW.booking_id;
    END IF;

    -- Jika status berubah ke 'rejected'
    IF NEW.payment_status = 'rejected' AND OLD.payment_status != 'rejected' THEN
        UPDATE bookings
        SET payment_status = 'cancelled'
        WHERE id = NEW.booking_id;
    END IF;

    -- Catat semua perubahan status ke log
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
```sql
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
```sql
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
---
![Cek Trigger yang Sudah Dibuat](https://raw.githubusercontent.com/TiwiMustikaDewi/LearnAndroidMobile/refs/heads/main/Screenshot%202026-06-05%20175216.png)

# 🔧 Stored Function
Function pasa Wandee terdapat 3 custom function yang disimpan di database dan bisa dilihat di phpMyAdmin tab Routines.

### Built-in Function

Built-in function MySQL yang digunakan di proyek Wandee terbagi tiga kelompok:

| Kelompok | Function | Lokasi |
|---|---|---|
| Tanggal & Waktu | `NOW()` | BookingModel.php — isi `created_at` saat INSERT booking |
| Tanggal & Waktu | `CURRENT_TIMESTAMP` | Default kolom `created_at` / `updated_at` / `dihapus_at` di hampir semua tabel |
| Tanggal & Waktu | `DATE_SUB(NOW(), INTERVAL 3 MINUTE)` | EVENT `auto_cancel_booking` — cek booking kadaluarsa |
| Tanggal & Waktu | `DATE_FORMAT` & `MONTH` | DashboardModel.php — grafik booking bulanan |
| Agregat | `AVG(rating)` | Trigger `after_insert_review` — hitung rata-rata rating |
| Agregat | `COUNT(*)` | Trigger (jumlah review) & DashboardModel (statistik kategori & bulanan) |
| Agregat | `ROUND(AVG(rating), 1)` | Trigger `after_insert_review` — bulatkan rating ke 1 desimal |
| Agregat | `SUM(payment_amount)` | DashboardModel.php — hitung total revenue dari payment verified |
| Agregat | `max(1,...)` & `min(5,...)` | ReviewModel.php — batasi nilai rating antara 1-5 |
| String | `CONCAT(...)` | Custom function `info_destinasi` |
| String | `UPPER(LEFT(nama, 3))` | Custom function `kode_destinasi` — ambil 3 karakter pertama jadi kapital |

### 1. cek_status_booking — Function Logika IF-ELSE
```sql
DELIMITER //
CREATE FUNCTION cek_status_booking(status VARCHAR(20))
RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    IF status = 'paid' THEN
        RETURN 'Pembayaran Terverifikasi';
    ELSEIF status = 'pending' THEN
        RETURN 'Menunggu Verifikasi';
    ELSEIF status = 'cancelled' THEN
        RETURN 'Booking Dibatalkan';
    ELSE
        RETURN 'Status Tidak Diketahui';
    END IF;
END //
DELIMITER ;
```
<img width="929" height="467" alt="image" src="https://github.com/user-attachments/assets/09142577-48c4-4f0b-b55b-5b0c8d0b06c5" />

### 2. kode_destinasi — Function Manipulasi String
```sql
DELIMITER //
CREATE FUNCTION kode_destinasi(nama_dest VARCHAR(100))
RETURNS VARCHAR(10)
DETERMINISTIC
BEGIN
    RETURN UPPER(LEFT(nama_dest, 3));
END //
DELIMITER ;
```
### 3. info_destinasi — Function CONCAT String
```sql
DELIMITER //
CREATE FUNCTION info_destinasi(
    nama_dest VARCHAR(100),
    lokasi VARCHAR(100)
)
RETURNS VARCHAR(200)
DETERMINISTIC
BEGIN
    RETURN CONCAT('Destinasi ', nama_dest, ' berlokasi di ', lokasi);
END //
DELIMITER ;
```
## 🧩 Fragmentasi Data

Fragmentasi pada Wandee diimplementasikan CREATE TABLE pada tabel destinations.

### 1. Fragmentasi Horizontal — Destinations per Kategori

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

### 2. Fragmentasi Vertikal — Data Publik vs Detail

Kolom `destinations` dipisah menjadi data publik dan data detail.

```sql
CREATE TABLE frag_dest_publik AS
SELECT id, title, location, category, image, trip_date, rating
FROM destinations;

CREATE TABLE frag_dest_detail AS
SELECT id, title, price, description, created_at
FROM destinations;
```

**JOIN**
```sql
SELECT p.*, d.price, d.description, d.created_at
FROM frag_dest_publik p
JOIN frag_dest_detail d ON p.id = d.id;
```

### 3. Fragmentasi Campuran — Destinasi Populer

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
