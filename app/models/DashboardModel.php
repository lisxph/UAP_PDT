<?php
class DashboardModel {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function countTrips(){
        $res = mysqli_query($this->conn, "SELECT id FROM destinations");
        return $res ? mysqli_num_rows($res) : 0;
    }

    public function countBookings(){
        $res = mysqli_query($this->conn, "SELECT id FROM bookings");
        return $res ? mysqli_num_rows($res) : 0;
    }

    public function countPayments(){
        $res = mysqli_query($this->conn, "SELECT id FROM payments");
        return $res ? mysqli_num_rows($res) : 0;
    }

    public function getTotalRevenue() {
        $res = mysqli_query($this->conn, "SELECT SUM(payment_amount) AS revenue FROM payments WHERE payment_status='verified'");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row && $row['revenue'] !== null ? (int)$row['revenue'] : 0;
    }

    public function getCategoryStats() {
        $res = mysqli_query($this->conn, "SELECT category, COUNT(*) as count FROM destinations GROUP BY category");
        $data = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getRecentBookings($limit = 5) {
        $limit = (int)$limit;
        $query = "SELECT b.*, u.name as user_name, d.title as destination_title 
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN destinations d ON b.destination_id = d.id
                  ORDER BY b.created_at DESC LIMIT $limit";
        $res = mysqli_query($this->conn, $query);
        $data = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getMonthlyBookingStats() {
        $query = "SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS count 
                  FROM bookings 
                  GROUP BY MONTH(created_at), DATE_FORMAT(created_at, '%b') 
                  ORDER BY MONTH(created_at) ASC";
        $res = mysqli_query($this->conn, $query);
        $data = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = $row;
            }
        }
        return $data;
    }
}
