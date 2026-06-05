<?php
class FavoriteModel {
    protected $conn;
    protected $destinationColumn = null;

    public function __construct($conn){
        $this->conn = $conn;
    }

    protected function destinationColumn(){
        if($this->destinationColumn !== null){
            return $this->destinationColumn;
        }

        $res = mysqli_query($this->conn, "SHOW COLUMNS FROM favorites LIKE 'destination_id'");
        $this->destinationColumn = ($res && mysqli_num_rows($res) > 0) ? 'destination_id' : 'trip_id';
        return $this->destinationColumn;
    }

    public function isFavorite($user_id, $destination_id){
        $user_id = (int)$user_id;
        $destination_id = (int)$destination_id;
        $column = $this->destinationColumn();
        $res = mysqli_query($this->conn, "SELECT id FROM favorites WHERE user_id='$user_id' AND $column='$destination_id' LIMIT 1");
        return $res && mysqli_num_rows($res) > 0;
    }

    public function add($user_id, $destination_id){
        $user_id = (int)$user_id;
        $destination_id = (int)$destination_id;
        $column = $this->destinationColumn();
        return mysqli_query($this->conn, "INSERT INTO favorites (user_id, $column) VALUES ('$user_id', '$destination_id')");
    }

    public function remove($user_id, $destination_id){
        $user_id = (int)$user_id;
        $destination_id = (int)$destination_id;
        $column = $this->destinationColumn();
        return mysqli_query($this->conn, "DELETE FROM favorites WHERE user_id='$user_id' AND $column='$destination_id'");
    }

    public function getByUser($user_id){
        $user_id = (int)$user_id;
        $column = $this->destinationColumn();
        $query = "SELECT f.*, f.$column AS destination_id, d.title AS destination_title, d.location AS destination_location, d.image AS destination_image, d.price AS destination_price
            FROM favorites f
            LEFT JOIN destinations d ON f.$column = d.id
            WHERE f.user_id='$user_id'
            ORDER BY f.created_at DESC";
        $res = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getDestinationIdsByUser($user_id){
        $user_id = (int)$user_id;
        $column = $this->destinationColumn();
        $res = mysqli_query($this->conn, "SELECT $column AS destination_id FROM favorites WHERE user_id='$user_id'");
        $ids = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $ids[] = (int)$row['destination_id'];
            }
        }
        return $ids;
    }
}
