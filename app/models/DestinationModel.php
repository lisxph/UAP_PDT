<?php
class DestinationModel {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    // -------------------------------------------------------
    // METHOD LAMA — tidak diubah sama sekali
    // -------------------------------------------------------

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
        $title       = mysqli_real_escape_string($this->conn, $data['title']);
        $location    = mysqli_real_escape_string($this->conn, $data['location']);
        $category    = mysqli_real_escape_string($this->conn, $data['category']);
        $price       = mysqli_real_escape_string($this->conn, $data['price']);
        $rating      = mysqli_real_escape_string($this->conn, $data['rating']);
        $description = mysqli_real_escape_string($this->conn, $data['description']);
        $image       = mysqli_real_escape_string($this->conn, $data['image']);

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
        $id          = (int)$id;
        $title       = mysqli_real_escape_string($this->conn, $data['title']);
        $location    = mysqli_real_escape_string($this->conn, $data['location']);
        $category    = mysqli_real_escape_string($this->conn, $data['category']);
        $price       = mysqli_real_escape_string($this->conn, $data['price']);
        $rating      = mysqli_real_escape_string($this->conn, $data['rating']);
        $description = mysqli_real_escape_string($this->conn, $data['description']);
        $image       = mysqli_real_escape_string($this->conn, $data['image']);

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
        $id     = (int)$id;
        $rating = mysqli_real_escape_string($this->conn, number_format((float)$rating, 1, '.', ''));
        return mysqli_query($this->conn, "UPDATE destinations SET rating='$rating' WHERE id='$id'");
    }

    // -------------------------------------------------------
    // RIGHT JOIN
    // Menampilkan SEMUA destinasi beserta jumlah booking-nya, termasuk destinasi yang belum pernah dibooking sama sekali
    // -------------------------------------------------------

    public function getDestinationsWithBookingCount(){
        $query = "SELECT d.id, d.title, d.location, d.category,
                         d.price, d.rating,
                         COUNT(b.id) AS booking_count
                  FROM bookings b
                  RIGHT JOIN destinations d ON b.destination_id = d.id
                  GROUP BY d.id, d.title, d.location, d.category, d.price, d.rating
                  ORDER BY booking_count DESC";
        $res  = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    // -------------------------------------------------------
    // UNION
    // Menggabungkan destinasi dari semua tabel fragmentasi menjadi satu daftar tanpa duplikat.
    // -------------------------------------------------------

    public function getAllFromFragments(){
        $query = "SELECT id, title, location, category, image, trip_date, price, rating
                  FROM frag_dest_gunung

                  UNION

                  SELECT id, title, location, category, image, trip_date, price, rating
                  FROM frag_dest_pantai

                  UNION

                  SELECT id, title, location, category, image, trip_date, price, rating
                  FROM frag_dest_lainnya

                  ORDER BY rating DESC";
        $res  = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }

    // -------------------------------------------------------
    // Simulasi FULL JOIN
    // Menampilkan semua user DAN semua destinasi, walau user belum pernah booking atau destinasi belum dibooking.
    // -------------------------------------------------------

    public function getFullJoinUsersDestinations(){
        $query = "SELECT u.id   AS user_id,   u.name  AS user_name,
                         d.id   AS dest_id,   d.title AS dest_title,
                         b.id   AS booking_id, b.payment_status
                  FROM users u
                  LEFT JOIN bookings b      ON u.id = b.user_id
                  LEFT JOIN destinations d  ON b.destination_id = d.id

                  UNION

                  SELECT u.id   AS user_id,   u.name  AS user_name,
                         d.id   AS dest_id,   d.title AS dest_title,
                         b.id   AS booking_id, b.payment_status
                  FROM users u
                  RIGHT JOIN bookings b     ON u.id = b.user_id
                  RIGHT JOIN destinations d ON b.destination_id = d.id

                  ORDER BY user_id ASC, dest_id ASC";
        $res  = mysqli_query($this->conn, $query);
        $data = [];
        if($res){
            while($row = mysqli_fetch_assoc($res)){
                $data[] = $row;
            }
        }
        return $data;
    }
    public function searchDestination($keyword)
{
    $stmt = mysqli_prepare(
        $this->conn,
        "CALL sp_search_destination(?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $keyword
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $data = [];

    while($row = mysqli_fetch_assoc($result))
    {
        $data[] = $row;
    }

    return $data;
}
}
