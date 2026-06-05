<?php
class UserModel {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function findByEmail($email){
        $email = mysqli_real_escape_string($this->conn, $email);
        $res = mysqli_query($this->conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function findById($id){
        $id = (int)$id;
        $res = mysqli_query($this->conn, "SELECT * FROM users WHERE id='$id' LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function updateWithPassword($id, $name, $email, $password, $photo){
        $id = (int)$id;
        $name = mysqli_real_escape_string($this->conn, $name);
        $email = mysqli_real_escape_string($this->conn, $email);
        $password = mysqli_real_escape_string($this->conn, $password);
        $photo = mysqli_real_escape_string($this->conn, $photo);
        return mysqli_query($this->conn, "UPDATE users SET name='$name', email='$email', password='$password', photo='$photo' WHERE id='$id'");
    }

    public function updateWithoutPassword($id, $name, $email, $photo){
        $id = (int)$id;
        $name = mysqli_real_escape_string($this->conn, $name);
        $email = mysqli_real_escape_string($this->conn, $email);
        $photo = mysqli_real_escape_string($this->conn, $photo);
        return mysqli_query($this->conn, "UPDATE users SET name='$name', email='$email', photo='$photo' WHERE id='$id'");
    }

    public function create($name, $email, $password){
        $name = mysqli_real_escape_string($this->conn, $name);
        $email = mysqli_real_escape_string($this->conn, $email);
        $password = mysqli_real_escape_string($this->conn, $password);
        mysqli_query($this->conn, "INSERT INTO users(name,email,password) VALUES('$name','$email','$password')");
        return mysqli_insert_id($this->conn);
    }

    public function countAll(){
        $res = mysqli_query($this->conn, "SELECT id FROM users");
        return $res ? mysqli_num_rows($res) : 0;
    }

    public function countFavorites($user_id){
        $user_id = (int)$user_id;
        $res = mysqli_query($this->conn, "SELECT id FROM favorites WHERE user_id='$user_id'");
        return $res ? mysqli_num_rows($res) : 0;
    }

    public function countBookings($user_id){
        $user_id = (int)$user_id;
        $res = mysqli_query($this->conn, "SELECT id FROM bookings WHERE user_id='$user_id'");
        return $res ? mysqli_num_rows($res) : 0;
    }

    public function countReviews($user_id){
        $user_id = (int)$user_id;
        $res = mysqli_query($this->conn, "SELECT id FROM reviews WHERE user_id='$user_id'");
        return $res ? mysqli_num_rows($res) : 0;
    }
}
