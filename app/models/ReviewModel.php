<?php
class ReviewModel {
    protected $conn;
    protected $destinationColumn = null;
    protected $textColumn = null;
    protected $imageColumn = null;

    public function __construct($conn){
        $this->conn = $conn;
    }

    protected function columnExists($column){
        $column = mysqli_real_escape_string($this->conn, $column);
        $res = mysqli_query($this->conn, "SHOW COLUMNS FROM reviews LIKE '$column'");
        return $res && mysqli_num_rows($res) > 0;
    }

    protected function destinationColumn(){
        if($this->destinationColumn !== null) return $this->destinationColumn;
        $this->destinationColumn = $this->columnExists('destination_id') ? 'destination_id' : 'trip_id';
        return $this->destinationColumn;
    }

    protected function textColumn(){
        if($this->textColumn !== null) return $this->textColumn;
        $this->textColumn = $this->columnExists('review_text') ? 'review_text' : 'review';
        return $this->textColumn;
    }

    protected function imageColumn(){
        if($this->imageColumn !== null) return $this->imageColumn;
        if($this->columnExists('review_image')){
            $this->imageColumn = 'review_image';
        } elseif($this->columnExists('image')){
            $this->imageColumn = 'image';
        } else {
            $this->imageColumn = '';
        }
        return $this->imageColumn;
    }

    public function create($data){
        $user_id = (int)$data['user_id'];
        $destination_id = (int)$data['destination_id'];
        $rating = max(1, min(5, (int)$data['rating']));
        $review_text = mysqli_real_escape_string($this->conn, $data['review_text']);
        $review_image = mysqli_real_escape_string($this->conn, $data['review_image'] ?? '');
        $destinationColumn = $this->destinationColumn();
        $textColumn = $this->textColumn();
        $imageColumn = $this->imageColumn();

        if($imageColumn){
            $query = "INSERT INTO reviews (user_id, $destinationColumn, rating, $textColumn, $imageColumn) VALUES ('$user_id', '$destination_id', '$rating', '$review_text', '$review_image')";
        } else {
            $query = "INSERT INTO reviews (user_id, $destinationColumn, rating, $textColumn) VALUES ('$user_id', '$destination_id', '$rating', '$review_text')";
        }

        return mysqli_query($this->conn, $query);
    }

    public function hasReviewed($user_id, $destination_id){
        $user_id = (int)$user_id;
        $destination_id = (int)$destination_id;
        $destinationColumn = $this->destinationColumn();
        $res = mysqli_query($this->conn, "SELECT id FROM reviews WHERE user_id='$user_id' AND $destinationColumn='$destination_id' LIMIT 1");
        return $res && mysqli_num_rows($res) > 0;
    }

    public function getReviewedDestinationIds($user_id){
        $user_id = (int)$user_id;
        $destinationColumn = $this->destinationColumn();
        $res = mysqli_query($this->conn, "SELECT $destinationColumn AS destination_id FROM reviews WHERE user_id='$user_id'");
        $ids = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $ids[] = (int)$row['destination_id'];
            }
        }
        return $ids;
    }

    public function getRatingStatsByDestinationIds(array $destinationIds){
        $ids = array_values(array_unique(array_map('intval', $destinationIds)));
        if(empty($ids)){
            return [];
        }

        $destinationColumn = $this->destinationColumn();
        $idList = implode(',', $ids);
        $res = mysqli_query($this->conn, "SELECT $destinationColumn AS destination_id, AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM reviews WHERE $destinationColumn IN ($idList) GROUP BY $destinationColumn");
        $stats = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $stats[(int)$row['destination_id']] = [
                    'avg_rating' => round((float)$row['avg_rating'], 1),
                    'total_reviews' => (int)$row['total_reviews']
                ];
            }
        }
        return $stats;
    }

    public function averageRatingByDestination($destination_id){
        $destination_id = (int)$destination_id;
        $destinationColumn = $this->destinationColumn();
        $res = mysqli_query($this->conn, "SELECT AVG(rating) AS avg_rating FROM reviews WHERE $destinationColumn='$destination_id'");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row && $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : null;
    }

    public function getAllWithUserDestination(){
        $destinationColumn = $this->destinationColumn();
        $textColumn = $this->textColumn();
        $imageColumn = $this->imageColumn();
        $imageSelect = $imageColumn ? "r.$imageColumn AS review_image" : "'' AS review_image";
        $query = "SELECT r.*, r.$destinationColumn AS destination_id, r.$textColumn AS review_text, $imageSelect, u.name AS user_name, d.title AS destination_title
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN destinations d ON r.$destinationColumn = d.id
            ORDER BY r.created_at DESC";
        $res = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getByDestination($destination_id){
        $destination_id = (int)$destination_id;
        $destinationColumn = $this->destinationColumn();
        $textColumn = $this->textColumn();
        $imageColumn = $this->imageColumn();
        $imageSelect = $imageColumn ? "r.$imageColumn AS review_image" : "'' AS review_image";
        
        $query = "SELECT r.*, r.$destinationColumn AS destination_id, r.$textColumn AS review_text, $imageSelect, u.name AS user_name, u.photo AS user_photo
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.$destinationColumn = '$destination_id'
            ORDER BY r.created_at DESC";
            
        $res = mysqli_query($this->conn, $query);
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
        $destinationColumn = $this->destinationColumn();
        $res = mysqli_query($this->conn, "SELECT *, $destinationColumn AS destination_id FROM reviews WHERE id='$id' LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function deleteById($id){
        $id = (int)$id;
        return mysqli_query($this->conn, "DELETE FROM reviews WHERE id='$id'");
    }
}
