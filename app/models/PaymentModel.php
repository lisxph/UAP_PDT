<?php
class PaymentModel {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function create($data){
        $booking_id = (int)$data['booking_id'];
        $payment_method = mysqli_real_escape_string($this->conn, $data['payment_method']);
        $payment_amount = (int)$data['payment_amount'];
        $unique_code_raw = trim((string)($data['unique_code'] ?? ''));
        $unique_code = $unique_code_raw === '' ? 'NULL' : "'" . mysqli_real_escape_string($this->conn, $unique_code_raw) . "'";
        $payment_status = mysqli_real_escape_string($this->conn, $data['payment_status'] ?? 'waiting');

        $query = "INSERT INTO payments (booking_id, payment_method, payment_amount, unique_code, payment_status) VALUES ('$booking_id', '$payment_method', '$payment_amount', $unique_code, '$payment_status')";
        mysqli_query($this->conn, $query);
        return mysqli_insert_id($this->conn);
    }

    public function findById($id){
        $id = (int)$id;
        $res = mysqli_query($this->conn, "SELECT * FROM payments WHERE id='$id' LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function findByBookingId($booking_id){
        $booking_id = (int)$booking_id;
        $res = mysqli_query($this->conn, "SELECT * FROM payments WHERE booking_id='$booking_id' ORDER BY created_at DESC LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function updateProof($id, $filename){
        $id = (int)$id;
        $filename = mysqli_real_escape_string($this->conn, $filename);
        return mysqli_query($this->conn, "UPDATE payments SET payment_proof='$filename', payment_status='waiting' WHERE id='$id'");
    }

    public function updateAmount($id, $amount){
        $id = (int)$id;
        $amount = (int)$amount;
        return mysqli_query($this->conn, "UPDATE payments SET payment_amount='$amount' WHERE id='$id'");
    }

    public function updateStatus($id, $status, $rejection_reason = ''){
    $id = (int)$id;

    $status = mysqli_real_escape_string(
        $this->conn,
        $status
    );

    $rejection_reason = mysqli_real_escape_string(
        $this->conn,
        $rejection_reason
    );

    return mysqli_query(
        $this->conn,
        "UPDATE payments
        SET
            payment_status='$status',
            rejection_reason='$rejection_reason'
        WHERE id='$id'"
    );
}

    public function getAllWithBooking($filter = null){
        $where = '';
        if($filter === 'waiting'){
    $where = "WHERE p.payment_status='waiting' AND b.trip_status <> 'cancelled'";
}
elseif($filter === 'verified'){
    $where = "WHERE p.payment_status='verified'";
}
elseif($filter === 'rejected'){
    $where = "WHERE p.payment_status='rejected' OR b.trip_status='cancelled'";
}
elseif($filter === 'completed'){
    $where = "WHERE b.trip_status='completed'";
}

        $query = "SELECT p.*, b.user_id, b.destination_id, b.total_people, b.total_price, b.payment_status AS booking_payment_status, b.trip_status, u.name AS user_name, d.title AS destination_title
            FROM payments p
            LEFT JOIN bookings b ON p.booking_id = b.id
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN destinations d ON b.destination_id = d.id
            $where
            ORDER BY p.created_at DESC";

        $res = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }
}
