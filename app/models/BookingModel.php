<?php
class BookingModel {
    protected $conn;
    protected $destinationColumn = null;
    protected $hasTripStatus = null;
    protected $hasPaymentUniqueCode = null;

    public function __construct($conn){
        $this->conn = $conn;
    }

    protected function destinationColumn(){
        if($this->destinationColumn !== null){
            return $this->destinationColumn;
        }

        $res = mysqli_query($this->conn, "SHOW COLUMNS FROM bookings LIKE 'destination_id'");
        $this->destinationColumn = ($res && mysqli_num_rows($res) > 0) ? 'destination_id' : 'trip_id';
        return $this->destinationColumn;
    }

    protected function hasTripStatus(){
        if($this->hasTripStatus !== null){
            return $this->hasTripStatus;
        }

        $res = mysqli_query($this->conn, "SHOW COLUMNS FROM bookings LIKE 'trip_status'");
        $this->hasTripStatus = $res && mysqli_num_rows($res) > 0;
        return $this->hasTripStatus;
    }

    protected function hasPaymentUniqueCode(){
        if($this->hasPaymentUniqueCode !== null){
            return $this->hasPaymentUniqueCode;
        }

        $res = mysqli_query($this->conn, "SHOW COLUMNS FROM payments LIKE 'unique_code'");
        $this->hasPaymentUniqueCode = $res && mysqli_num_rows($res) > 0;
        return $this->hasPaymentUniqueCode;
    }

    public function create($data){
        $user_id = (int)$data['user_id'];
        $destination_id = (int)$data['destination_id'];
        $total_people = (int)$data['total_people'];
        $total_price = (int)$data['total_price'];
        $payment_status = mysqli_real_escape_string($this->conn, $data['payment_status'] ?? 'pending');
        $trip_status = mysqli_real_escape_string($this->conn, $data['trip_status'] ?? 'new');
        $column = $this->destinationColumn();

        if($this->hasTripStatus()){
    $query = "INSERT INTO bookings (
    user_id,
    $column,
    total_people,
    total_price,
    payment_status,
    created_at
) VALUES (
    '$user_id',
    '$destination_id',
    '$total_people',
    '$total_price',
    '$payment_status',
    NOW()
)";

        } else {
            $query = "INSERT INTO bookings (user_id, $column, total_people, total_price, payment_status) VALUES ('$user_id', '$destination_id', '$total_people', '$total_price', '$payment_status')";
        }
        mysqli_query($this->conn, $query);
        return mysqli_insert_id($this->conn);
    }

    public function findById($id){
        $id = (int)$id;
        $column = $this->destinationColumn();
        $tripStatusSelect = $this->hasTripStatus() ? "trip_status" : "'new' AS trip_status";
        $res = mysqli_query($this->conn, "SELECT *, $column AS destination_id, $tripStatusSelect FROM bookings WHERE id='$id' LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function getByUser($user_id){
        $user_id = (int)$user_id;
        $column = $this->destinationColumn();
        $tripStatusSelect = $this->hasTripStatus() ? "b.trip_status" : "'new' AS trip_status";
        $uniqueCodeSelect = $this->hasPaymentUniqueCode() ? "p.unique_code" : "0 AS unique_code";
        $query = "SELECT b.*, b.$column AS destination_id, $tripStatusSelect, d.title AS destination_title, d.location AS destination_location, d.image AS destination_image, d.price AS destination_price,
            p.id AS payment_id, $uniqueCodeSelect, p.payment_status AS payment_status_detail,
            cek_status_booking(b.payment_status) AS keterangan_status
            FROM bookings b
            LEFT JOIN destinations d ON b.$column = d.id
            LEFT JOIN payments p ON p.booking_id = b.id
            WHERE b.user_id='$user_id'
            ORDER BY b.created_at DESC";
        $res = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getCompletedByUser($user_id){
        $user_id = (int)$user_id;
        if(!$this->hasTripStatus()){
            return [];
        }

        $column = $this->destinationColumn();
        $query = "SELECT b.*, b.$column AS destination_id, d.title AS destination_title, d.location AS destination_location, d.image AS destination_image, d.rating AS destination_rating, d.trip_date AS destination_trip_date
            FROM bookings b
            LEFT JOIN destinations d ON b.$column = d.id
            WHERE b.user_id='$user_id' AND b.trip_status='completed'
            ORDER BY b.created_at DESC";

        $res = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function updateTripStatus($id, $status){
        if(!$this->hasTripStatus()){
            return true;
        }

        $id = (int)$id;
        $status = mysqli_real_escape_string($this->conn, $status);
        return mysqli_query($this->conn, "UPDATE bookings SET trip_status='$status' WHERE id='$id'");
    }

    public function updatePaymentStatus($id, $status){
        $id = (int)$id;
        $status = mysqli_real_escape_string($this->conn, $status);
        return mysqli_query($this->conn, "UPDATE bookings SET payment_status='$status' WHERE id='$id'");
    }

    public function createWithPayment($bookingData, $paymentData){
        mysqli_autocommit($this->conn, false);
        mysqli_begin_transaction($this->conn);

        // Log file sementara untuk debug — hapus setelah demo berhasil
        $logFile = __DIR__ . '/../../deadlock_debug.log';
        $logTime = date('H:i:s');

        try {
            $user_id        = (int)$bookingData['user_id'];
            $destination_id = (int)$bookingData['destination_id'];
            $total_people   = (int)$bookingData['total_people'];
            $total_price    = (int)$bookingData['total_price'];
            $payment_status = mysqli_real_escape_string($this->conn, $bookingData['payment_status'] ?? 'pending');
            $column         = $this->destinationColumn();

            file_put_contents($logFile, "[$logTime] user=$user_id dest=$destination_id — mulai\n", FILE_APPEND);

            // ----------------------------------------------------------------
            // SIMULASI DEADLOCK — pola A-B saling silang
            //
            // Device A (user ganjil): lock destinations → sleep → lock payments row 1
            // Device B (user genap) : lock payments row 1 → sleep → lock destinations
            //
            // A pegang destinations, butuh payments
            // B pegang payments, butuh destinations
            // → Circular wait → DEADLOCK
            // ----------------------------------------------------------------

            $isOddUser = ($user_id % 2 === 1);

            if ($isOddUser) {
                // Urutan A: destinations dulu → payments
                file_put_contents($logFile, "[$logTime] user=$user_id — [A] lock destinations($destination_id)\n", FILE_APPEND);
                $r = mysqli_query($this->conn, "SELECT id FROM destinations WHERE id = $destination_id FOR UPDATE");
                if (!$r) {
                    $errno = mysqli_errno($this->conn); $errmsg = mysqli_error($this->conn);
                    file_put_contents($logFile, "[$logTime] user=$user_id — [A] GAGAL lock destinations errno=$errno $errmsg\n", FILE_APPEND);
                    mysqli_rollback($this->conn); mysqli_autocommit($this->conn, true);
                    return ['error' => $errmsg, 'errno' => $errno];
                }
                file_put_contents($logFile, "[$logTime] user=$user_id — [A] OK lock destinations, sleep 8s\n", FILE_APPEND);
                mysqli_query($this->conn, "SELECT SLEEP(8)");
                file_put_contents($logFile, "[$logTime] user=$user_id — [A] coba lock payments row 29\n", FILE_APPEND);
                $r2 = mysqli_query($this->conn, "SELECT id FROM payments WHERE id = 29 FOR UPDATE");
                $errno2 = mysqli_errno($this->conn);
                file_put_contents($logFile, "[$logTime] user=$user_id — [A] lock payments row 29 result=" . ($r2 ? 'OK' : 'FAIL') . " errno=$errno2\n", FILE_APPEND);
                if (!$r2) {
                    $errmsg = mysqli_error($this->conn);
                    mysqli_rollback($this->conn); mysqli_autocommit($this->conn, true);
                    return ['error' => $errmsg, 'errno' => $errno2];
                }
            } else {
                // Urutan B: payments dulu → destinations (urutan TERBALIK dari A)
                file_put_contents($logFile, "[$logTime] user=$user_id — [B] lock payments row 29\n", FILE_APPEND);
                $r = mysqli_query($this->conn, "SELECT id FROM payments WHERE id = 29 FOR UPDATE");
                if (!$r) {
                    $errno = mysqli_errno($this->conn); $errmsg = mysqli_error($this->conn);
                    file_put_contents($logFile, "[$logTime] user=$user_id — [B] GAGAL lock payments row 29 errno=$errno $errmsg\n", FILE_APPEND);
                    mysqli_rollback($this->conn); mysqli_autocommit($this->conn, true);
                    return ['error' => $errmsg, 'errno' => $errno];
                }
                file_put_contents($logFile, "[$logTime] user=$user_id — [B] OK lock payments row 29, sleep 2s\n", FILE_APPEND);
                mysqli_query($this->conn, "SELECT SLEEP(2)");
                file_put_contents($logFile, "[$logTime] user=$user_id — [B] coba lock destinations($destination_id)\n", FILE_APPEND);
                $r2 = mysqli_query($this->conn, "SELECT id FROM destinations WHERE id = $destination_id FOR UPDATE");
                $errno2 = mysqli_errno($this->conn);
                file_put_contents($logFile, "[$logTime] user=$user_id — [B] lock destinations result=" . ($r2 ? 'OK' : 'FAIL') . " errno=$errno2\n", FILE_APPEND);
                if (!$r2) {
                    $errmsg = mysqli_error($this->conn);
                    mysqli_rollback($this->conn); mysqli_autocommit($this->conn, true);
                    return ['error' => $errmsg, 'errno' => $errno2];
                }
            }
            // ----------------------------------------------------------------

            if($this->hasTripStatus()){
                $q1 = "INSERT INTO bookings (user_id, $column, total_people, total_price, payment_status, created_at)
                        VALUES ('$user_id','$destination_id','$total_people','$total_price','$payment_status', NOW())";
            } else {
                $q1 = "INSERT INTO bookings (user_id, $column, total_people, total_price, payment_status)
                        VALUES ('$user_id','$destination_id','$total_people','$total_price','$payment_status')";
            }

            $r1 = mysqli_query($this->conn, $q1);
            if(!$r1) throw new Exception("Gagal membuat booking: " . mysqli_error($this->conn));

            $booking_id = mysqli_insert_id($this->conn);
            if(!$booking_id) throw new Exception("Booking ID tidak valid.");

            $payment_method  = mysqli_real_escape_string($this->conn, $paymentData['payment_method']);
            $payment_amount  = (int)$paymentData['payment_amount'];
            $unique_code_raw = trim((string)($paymentData['unique_code'] ?? ''));
            $unique_code     = $unique_code_raw === '' ? 'NULL' : "'" . mysqli_real_escape_string($this->conn, $unique_code_raw) . "'";
            $p_status        = mysqli_real_escape_string($this->conn, $paymentData['payment_status'] ?? 'waiting');

            $q2 = "INSERT INTO payments (booking_id, payment_method, payment_amount, unique_code, payment_status)
                    VALUES ('$booking_id','$payment_method','$payment_amount',$unique_code,'$p_status')";

            $r2 = mysqli_query($this->conn, $q2);
            if(!$r2) throw new Exception("Gagal membuat payment: " . mysqli_error($this->conn));

            $payment_id = mysqli_insert_id($this->conn);
            if(!$payment_id) throw new Exception("Payment ID tidak valid.");

            mysqli_commit($this->conn);
            mysqli_autocommit($this->conn, true);
            return ['booking_id' => $booking_id, 'payment_id' => $payment_id];

        } catch(Exception $e){
            // Simpan errno SEBELUM rollback karena rollback akan mereset errno ke 0
            $errno = mysqli_errno($this->conn);
            mysqli_rollback($this->conn);
            mysqli_autocommit($this->conn, true);
            return [
                'error' => $e->getMessage(),
                'errno' => $errno,
            ];
        }
    }
}
