<?php
class DestinationModel {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function getAllOrderedByRating(){
        $res = mysqli_query($this->conn, "SELECT * FROM destinations ORDER BY rating DESC");
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getAllOrderedById(){
        $res = mysqli_query($this->conn, "SELECT * FROM destinations ORDER BY id DESC");
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function findById($id){
        $id = (int)$id;
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM destinations WHERE id = ?");
        if(!$stmt){
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function create($data){
        $title = mysqli_real_escape_string($this->conn, $data['title']);
        $location = mysqli_real_escape_string($this->conn, $data['location']);
        $category = mysqli_real_escape_string($this->conn, $data['category']);
        $price = mysqli_real_escape_string($this->conn, $data['price']);
        $rating = mysqli_real_escape_string($this->conn, $data['rating']);
        $description = mysqli_real_escape_string($this->conn, $data['description']);
        $image = mysqli_real_escape_string($this->conn, $data['image']);

        $query = "INSERT INTO destinations
            (title, location, category, price, rating, description, image)
            VALUES
            ('$title','$location','$category','$price','$rating','$description','$image')";

        return mysqli_query($this->conn, $query);
    }

    public function deleteById($id){
        $id = (int)$id;
        return mysqli_query($this->conn, "DELETE FROM destinations WHERE id='$id'");
    }

    public function updateById($id, $data){
        $id = (int)$id;
        $title = mysqli_real_escape_string($this->conn, $data['title']);
        $location = mysqli_real_escape_string($this->conn, $data['location']);
        $category = mysqli_real_escape_string($this->conn, $data['category']);
        $price = mysqli_real_escape_string($this->conn, $data['price']);
        $rating = mysqli_real_escape_string($this->conn, $data['rating']);
        $description = mysqli_real_escape_string($this->conn, $data['description']);
        $image = mysqli_real_escape_string($this->conn, $data['image']);

        $query = "UPDATE destinations SET
            title='$title',
            location='$location',
            category='$category',
            price='$price',
            rating='$rating',
            description='$description',
            image='$image'
            WHERE id='$id'";

        return mysqli_query($this->conn, $query);
    }

    public function updateRating($id, $rating){
        $id = (int)$id;
        $rating = mysqli_real_escape_string($this->conn, number_format((float)$rating, 1, '.', ''));
        return mysqli_query($this->conn, "UPDATE destinations SET rating='$rating' WHERE id='$id'");
    }
}
