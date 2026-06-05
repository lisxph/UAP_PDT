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
            p.id AS payment_id, $uniqueCodeSelect, p.payment_status AS payment_status_detail
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
}
