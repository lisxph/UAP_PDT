<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DestinationModel.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/FavoriteModel.php';
require_once __DIR__ . '/../models/ReviewModel.php';

class UserController extends Controller {
    protected $conn;
    protected $model;
    protected $destinationModel;
    protected $bookingModel;
    protected $paymentModel;
    protected $favoriteModel;
    protected $reviewModel;

    public function __construct($conn){
        $this->conn = $conn;
        $this->model = new UserModel($conn);
        $this->destinationModel = new DestinationModel($conn);
        $this->bookingModel = new BookingModel($conn);
        $this->paymentModel = new PaymentModel($conn);
        $this->favoriteModel = new FavoriteModel($conn);
        $this->reviewModel = new ReviewModel($conn);
    }

    protected function requireUser()
{
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    $timeout = 3000; 

    // belum login
    if(!isset($_SESSION['user_id'])){
        header("Location: /wandee/auth/loginregister");
        exit;
    }

    if(($_SESSION['role'] ?? '') === 'admin'){
        header("Location: /wandee/admin/dashboard");
        exit;
    }

    // cek inactivity
    if(
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > $timeout
    ){

        session_unset();
        session_destroy();

        header("Location: /wandee/auth/loginregister?expired=1");
        exit;
    }

    // update aktivitas terakhir
    $_SESSION['last_activity'] = time();

    return (int) $_SESSION['user_id'];
}

    public function index(){
        $user_id = $this->requireUser();
        $name = $_SESSION['name'];
        $destinations = $this->destinationModel->getAllOrderedByRating();
        $destinationIds = array_map(function($dest){
            return (int)$dest['id'];
        }, $destinations);
        $ratingStats = $this->reviewModel->getRatingStatsByDestinationIds($destinationIds);
        $favoriteDestinationIds = $this->favoriteModel->getDestinationIdsByUser($user_id);
        require __DIR__ . '/../views/user/index.php';
    }

    public function favorite(){
        $user_id = $this->requireUser();
        $favorites = $this->favoriteModel->getByUser($user_id);
        require __DIR__ . '/../views/user/favorite.php';
    }

    public function riwayat(){
        $user_id = $this->requireUser();
        $bookings = $this->bookingModel->getByUser($user_id);
        require __DIR__ . '/../views/user/riwayat.php';
    }

    public function ulasan(){
        $user_id = $this->requireUser();
        $bookings = $this->bookingModel->getCompletedByUser($user_id);
        $reviewedDestinationIds = $this->reviewModel->getReviewedDestinationIds($user_id);
        $eligibleBookings = array_values(array_filter($bookings, function($booking) use ($reviewedDestinationIds){
            return !in_array((int)$booking['destination_id'], $reviewedDestinationIds, true);
        }));
        $selectedBooking = $eligibleBookings[0] ?? null;
        $reviewMessage = $_SESSION['review_message'] ?? null;
        $reviewError = $_SESSION['review_error'] ?? null;
        unset($_SESSION['review_message'], $_SESSION['review_error']);
        require __DIR__ . '/../views/user/ulasan.php';
    }

    public function payment(){
        $user_id = $this->requireUser();
        $destination_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if($destination_id <= 0){
            header('Location: /wandee/#destinations');
            exit;
        }

        $destination = $this->destinationModel->findById($destination_id);
        if(!$destination){
            header('Location: /wandee/#destinations');
            exit;
        }

        $quantity = isset($_GET['people']) ? max(1, (int)$_GET['people']) : 1;
        $price = $this->priceToInt($destination['price'] ?? 0);
        $total_price = $price * $quantity;
        require __DIR__ . '/../views/user/payment.php';
    }

    public function payment_detail(){
        $user_id = $this->requireUser();
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

        $booking = $this->bookingModel->findById($booking_id);
        $payment = $this->paymentModel->findById($payment_id);

        if(!$booking || !$payment || (int)$booking['user_id'] !== $user_id || (int)$payment['booking_id'] !== (int)$booking['id']){
            header('Location: /wandee/user/riwayat');
            exit;
        }

        $destination = $this->destinationModel->findById($booking['destination_id']);
        $price = $this->priceToInt($destination['price'] ?? 0);
        $computed_amount = $price * (int)$booking['total_people'];
        if($computed_amount > 0 && (int)$payment['payment_amount'] < 1000){
            $this->paymentModel->updateAmount($payment['id'], $computed_amount);
            $payment['payment_amount'] = $computed_amount;
        }
        $voucher_code = trim((string)($payment['unique_code'] ?? ''));
        $payment_total = (int)$payment['payment_amount'];
        require __DIR__ . '/../views/user/payment_detail.php';
    }

    public function payment_status(){
        $user_id = $this->requireUser();
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        $booking = $this->bookingModel->findById($booking_id);

        if(!$booking || (int)$booking['user_id'] !== $user_id){
            header('Location: /wandee/user/profile');
            exit;
        }

        $payment = $this->paymentModel->findByBookingId($booking_id);
        $destination = $this->destinationModel->findById($booking['destination_id']);
        $price = $this->priceToInt($destination['price'] ?? 0);
        $computed_amount = $price * (int)$booking['total_people'];
        if($payment && $computed_amount > 0 && (int)$payment['payment_amount'] < 1000){
            $this->paymentModel->updateAmount($payment['id'], $computed_amount);
            $payment['payment_amount'] = $computed_amount;
        }
        $voucher_code = trim((string)($payment['unique_code'] ?? ''));
        $payment_total = $payment ? (int)$payment['payment_amount'] : 0;
        require __DIR__ . '/../views/user/payment_status.php';
    }

    public function profile(){
        $user_id = $this->requireUser();
        $user = $this->model->findById($user_id);
        $favorites_count = $this->model->countFavorites($user_id);
        $bookings_count = $this->model->countBookings($user_id);
        $reviews_count = $this->model->countReviews($user_id);

        require __DIR__ . '/../views/user/profile.php';
    }

    public function detail(){
        $user_id = $this->requireUser();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if($id <= 0){
            header("Location: /wandee/#destinations");
            exit;
        }

        $dest = $this->destinationModel->findById($id);

        if(!$dest){
            header("Location: /wandee/#destinations");
            exit;
        }

        $detailData = $this->prepareDestinationDetail($dest);
        $category = $detailData['category'];
        $title = $detailData['title'];
        $days = $detailData['days'];
        $itineraries = $detailData['itineraries'];
        $facilities = $detailData['facilities'];
        $facility_icons = $detailData['facility_icons'];
        $isFavorite = $this->favoriteModel->isFavorite($user_id, $id);
        $reviews = $this->reviewModel->getByDestination($id);

        require __DIR__ . '/../views/user/detail.php';
    }

    public function update_profile(){
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user_id'])){
            header('Location: /wandee/auth/loginregister');
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->model->findById($user_id);
        $photo_name = $user['photo'] ?? '';

        if(!empty($_FILES['photo']['name'])){
            $file_name = $_FILES['photo']['name'];
            $tmp_name = $_FILES['photo']['tmp_name'];
            $new_name = time() . '_' . $file_name;
            $destFolder = __DIR__ . '/../../public/uploads/profile/';
            if(!is_dir($destFolder)) mkdir($destFolder, 0755, true);
            move_uploaded_file($tmp_name, $destFolder . $new_name);
            $photo_name = $new_name;
        }

        if(!empty($password)){
            $password_h = md5($password);
            $this->model->updateWithPassword($user_id, $name, $email, $password_h, $photo_name);
        } else {
            $this->model->updateWithoutPassword($user_id, $name, $email, $photo_name);
        }

        $_SESSION['name'] = $name;

        $profileRoute = ($_SESSION['role'] ?? '') === 'admin' ? '/wandee/admin/profile' : '/wandee/user/profile';
        header('Location: ' . $profileRoute);
        exit;
    }

    public function payment_init(){
        $user_id = $this->requireUser();
        $destination_id = isset($_POST['destination_id']) ? (int)$_POST['destination_id'] : 0;
        $people = isset($_POST['total_people']) ? max(1, (int)$_POST['total_people']) : 1;
        $method = $_POST['payment_method'] ?? '';

        if($destination_id <= 0 || !$method){
            header('Location: /wandee/user/payment?id=' . $destination_id);
            exit;
        }

        $destination = $this->destinationModel->findById($destination_id);
        if(!$destination){
            header('Location: /wandee/#destinations');
            exit;
        }

        $total_price = $this->priceToInt($destination['price'] ?? 0) * $people;
        $voucher_code = '';

        $result = $this->bookingModel->createWithPayment([
            'user_id'        => $user_id,
            'destination_id' => $destination_id,
            'total_people'   => $people,
            'total_price'    => $total_price,
            'payment_status' => 'pending',
            'trip_status'    => 'new',
        ],
        [
            'payment_method' => $method,
            'payment_amount' => $total_price,
            'unique_code'    => $voucher_code,
            'payment_status' => 'waiting',
        ]
    );

    if(isset($result['error'])){
        // Deadlock: errno 1213
        if(($result['errno'] ?? 0) === 1213){
            header('Location: /wandee/user/payment?id=' . $destination_id . '&error=deadlock');
        } else {
            header('Location: /wandee/user/payment?id=' . $destination_id . '&error=booking_failed');
        }
        exit;
    }

    $booking_id = $result['booking_id'];
    $payment_id = $result['payment_id'];

    header('Location: /wandee/user/payment_detail?booking_id=' . $booking_id . '&payment_id=' . $payment_id);
    exit;
    }

    public function payment_upload(){
        $user_id = $this->requireUser();
        $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
        $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $payment = $this->paymentModel->findById($payment_id);
        $booking = $this->bookingModel->findById($booking_id);

        if(!$payment || !$booking || (int)$booking['user_id'] !== $user_id || (int)$payment['booking_id'] !== (int)$booking['id']){
            header('Location: /wandee/user/profile');
            exit;
        }

        if(!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK){
            header('Location: /wandee/user/payment_detail?booking_id=' . $booking_id . '&payment_id=' . $payment_id);
            exit;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $mime = mime_content_type($_FILES['payment_proof']['tmp_name']);
        if(!in_array($mime, $allowed, true) || $_FILES['payment_proof']['size'] > 5 * 1024 * 1024){
            header('Location: /wandee/user/payment_detail?booking_id=' . $booking_id . '&payment_id=' . $payment_id);
            exit;
        }

        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\.\-]/', '_', basename($_FILES['payment_proof']['name']));
        $destFolder = __DIR__ . '/../../public/uploads/payments/';
        if(!is_dir($destFolder)) mkdir($destFolder, 0755, true);
        move_uploaded_file($_FILES['payment_proof']['tmp_name'], $destFolder . $filename);
        $this->paymentModel->updateProof($payment_id, $filename);

        header('Location: /wandee/user/payment_status?booking_id=' . $booking_id);
        exit;
    }

    public function favorite_toggle(){
        $user_id = $this->requireUser();
        $destination_id = isset($_POST['destination_id']) ? (int)$_POST['destination_id'] : 0;

        if($destination_id <= 0){
            header('Location: /wandee/user/favorite');
            exit;
        }

        if($this->favoriteModel->isFavorite($user_id, $destination_id)){
            $this->favoriteModel->remove($user_id, $destination_id);
        } else {
            $this->favoriteModel->add($user_id, $destination_id);
        }

        header('Location: /wandee/user/detail?id=' . $destination_id);
        exit;
    }

    public function submit_review(){
        $user_id = $this->requireUser();
        $destination_id = isset($_POST['destination_id']) ? (int)$_POST['destination_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $review_text = trim($_POST['review'] ?? '');

        if($destination_id <= 0 || $rating < 1 || $rating > 5 || $review_text === ''){
            $_SESSION['review_error'] = 'Pilih destinasi, rating 1-5, dan isi ulasan terlebih dahulu.';
            header('Location: /wandee/user/ulasan');
            exit;
        }

        if(strlen($review_text) > 500){
            $_SESSION['review_error'] = 'Ulasan maksimal 500 karakter.';
            header('Location: /wandee/user/ulasan');
            exit;
        }

        $eligible = false;
        foreach($this->bookingModel->getCompletedByUser($user_id) as $booking){
            if((int)$booking['destination_id'] === $destination_id){
                $eligible = true;
                break;
            }
        }

        if(!$eligible){
            $_SESSION['review_error'] = 'Ulasan hanya bisa diberikan setelah perjalanan destinasi tersebut selesai.';
            header('Location: /wandee/user/ulasan');
            exit;
        }

        if($this->reviewModel->hasReviewed($user_id, $destination_id)){
            $_SESSION['review_error'] = 'Anda sudah memberi ulasan untuk destinasi ini.';
            header('Location: /wandee/user/ulasan');
            exit;
        }

        $review_image = '';
        if(isset($_FILES['review_photo']) && $_FILES['review_photo']['error'] !== UPLOAD_ERR_NO_FILE){
            if($_FILES['review_photo']['error'] !== UPLOAD_ERR_OK){
                $_SESSION['review_error'] = 'Upload foto ulasan gagal.';
                header('Location: /wandee/user/ulasan');
                exit;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
            $mime = mime_content_type($_FILES['review_photo']['tmp_name']);
            if(!in_array($mime, $allowed, true) || $_FILES['review_photo']['size'] > 5 * 1024 * 1024){
                $_SESSION['review_error'] = 'Foto ulasan harus JPG/JPEG/PNG dan maksimal 5 MB.';
                header('Location: /wandee/user/ulasan');
                exit;
            }

            $review_image = time() . '_' . preg_replace('/[^A-Za-z0-9_\.\-]/', '_', basename($_FILES['review_photo']['name']));
            $destFolder = __DIR__ . '/../../public/uploads/reviews/';
            if(!is_dir($destFolder)) mkdir($destFolder, 0755, true);
            move_uploaded_file($_FILES['review_photo']['tmp_name'], $destFolder . $review_image);
        }

        $this->reviewModel->create([
            'user_id' => $user_id,
            'destination_id' => $destination_id,
            'rating' => $rating,
            'review_text' => $review_text,
            'review_image' => $review_image
        ]);

        $avgRating = $this->reviewModel->averageRatingByDestination($destination_id);
        if($avgRating !== null){
            $this->destinationModel->updateRating($destination_id, $avgRating);
        }

        $_SESSION['review_message'] = 'Ulasan berhasil dikirim. Terima kasih sudah berbagi pengalaman.';
        header('Location: /wandee/user/ulasan');
        exit;
    }

    protected function prepareDestinationDetail($dest){
        $category = $dest['category'];
        $title = $dest['title'];
        $days = 1;

        if(!empty($dest['trip_date'])){
            preg_match('/(\d+)\s+\w+\s*-\s*(\d+)/', $dest['trip_date'], $m);
            if(!empty($m[1]) && !empty($m[2])){
                $days = abs((int)$m[2] - (int)$m[1]) + 1;
                if($days < 1) $days = 1;
            }
        }

        $itineraries = [];
        $facilities = [];

        if($category === 'Gunung'){
            $itineraries = [
                ['label' => 'Hari ke-1', 'title' => 'Perjalanan & Aklimatisasi', 'desc' => 'Tiba di basecamp, registrasi, briefing pendakian, istirahat dan persiapan perlengkapan.', 'time' => '10.00 - 18.00 WIB'],
                ['label' => 'Hari ke-2', 'title' => 'Summit Attack & Sunrise', 'desc' => 'Dini hari mendaki ke puncak untuk menikmati sunrise terbaik dan panorama alam yang menakjubkan.', 'time' => '02.00 - 14.00 WIB'],
            ];
            if($days >= 3){
                $itineraries[] = ['label' => 'Hari ke-3', 'title' => 'Turun & Kembali', 'desc' => 'Turun gunung, pembersihan peralatan, oleh-oleh, dan perjalanan pulang ke kota.', 'time' => '07.00 - 18.00 WIB'];
            }
            $facilities = ['Transportasi', 'Tenda & Matras', 'Porter', 'Makan', 'P3K'];
        } elseif($category === 'Pantai'){
            $itineraries = [
                ['label' => 'Hari ke-1', 'title' => 'Tiba & Explore Pantai', 'desc' => 'Tiba di lokasi, check-in penginapan, eksplorasi pantai, snorkeling dan menikmati senja.', 'time' => '10.00 - 18.00 WIB'],
                ['label' => 'Hari ke-2', 'title' => 'Island Hopping & Diving', 'desc' => 'Keliling pulau-pulau sekitar, diving, menikmati keindahan bawah laut dan kuliner lokal.', 'time' => '07.00 - 17.00 WIB'],
            ];
            if($days >= 3){
                $itineraries[] = ['label' => 'Hari ke-3', 'title' => 'Sunrise & Kepulangan', 'desc' => 'Menikmati sunrise di pantai, sarapan khas lokal, belanja oleh-oleh, dan perjalanan pulang.', 'time' => '05.00 - 15.00 WIB'];
            }
            $facilities = ['Transportasi', 'Penginapan', 'Snorkeling Gear', 'Makan', 'Pemandu Lokal'];
        } elseif($category === 'Air Terjun'){
            $itineraries = [
                ['label' => 'Hari ke-1', 'title' => 'Tiba & Trekking Awal', 'desc' => 'Tiba di lokasi, trekking ringan menuju basecamp, menikmati suasana alam sekitar.', 'time' => '09.00 - 17.00 WIB'],
                ['label' => 'Hari ke-2', 'title' => 'Kunjungi Air Terjun Utama', 'desc' => 'Trekking ke air terjun utama, mandi di kolam alami, foto-foto, dan menikmati udara segar pegunungan.', 'time' => '07.00 - 16.00 WIB'],
            ];
            if($days >= 3){
                $itineraries[] = ['label' => 'Hari ke-3', 'title' => 'Air Terjun Tersembunyi & Pulang', 'desc' => 'Menjelajahi air terjun tersembunyi di sekitar lokasi, piknik, oleh-oleh, dan kembali ke kota.', 'time' => '08.00 - 18.00 WIB'];
            }
            $facilities = ['Transportasi', 'Pemandu', 'Makan Siang', 'Tiket Masuk', 'Dokumentasi'];
        } elseif($category === 'Kota'){
            $itineraries = [
                ['label' => 'Hari ke-1', 'title' => 'City Tour & Kuliner', 'desc' => 'Kunjungi landmark ikonik kota, wisata kuliner lokal, museum, dan pusat perbelanjaan.', 'time' => '09.00 - 20.00 WIB'],
                ['label' => 'Hari ke-2', 'title' => 'Wisata Budaya & Belanja', 'desc' => 'Mengunjungi situs budaya dan bersejarah, belanja oleh-oleh khas, dan hiburan malam kota.', 'time' => '10.00 - 21.00 WIB'],
            ];
            if($days >= 3){
                $itineraries[] = ['label' => 'Hari ke-3', 'title' => 'Wisata Alam Pinggir Kota & Pulang', 'desc' => 'Kunjungi destinasi alam di sekitar kota, sarapan khas, dan perjalanan pulang.', 'time' => '08.00 - 17.00 WIB'];
            }
            $facilities = ['Transportasi', 'Penginapan', 'Tiket Wisata', 'Makan', 'Pemandu'];
        } else {
            $itineraries = [
                ['label' => 'Hari ke-1', 'title' => 'Tiba & Eksplorasi', 'desc' => 'Tiba di lokasi, orientasi, eksplorasi area sekitar dan menikmati suasana setempat.', 'time' => '09.00 - 17.00 WIB'],
            ];
            $facilities = ['Transportasi', 'Pemandu', 'Makan', 'Dokumentasi'];
        }

        $facility_icons = [
            'Transportasi' => 'bus',
            'Penginapan' => 'hotel',
            'Tiket Masuk' => 'ticket',
            'Tiket Wisata' => 'ticket',
            'Makan' => 'utensils',
            'Makan Siang' => 'utensils',
            'Dokumentasi' => 'camera',
            'Pemandu' => 'user-check',
            'Pemandu Lokal' => 'user-check',
            'Porter' => 'backpack',
            'P3K' => 'heart-pulse',
            'Snorkeling Gear' => 'waves',
            'Tenda & Matras' => 'tent',
            'Asuransi' => 'shield-check',
        ];

        return [
            'category' => $category,
            'title' => $title,
            'days' => $days,
            'itineraries' => $itineraries,
            'facilities' => $facilities,
            'facility_icons' => $facility_icons,
        ];
    }

    protected function priceToInt($price){
        $raw = strtolower(trim((string)$price));
        $raw = str_replace(['rp', 'idr', ' '], '', $raw);

        if(strpos($raw, 'jt') !== false || strpos($raw, 'juta') !== false){
            $number = str_replace(['juta', 'jt'], '', $raw);
            $number = str_replace(',', '.', $number);
            return (int)round((float)$number * 1000000);
        }

        if(preg_match('/^\d+[.,]\d+$/', $raw)){
            $number = str_replace(',', '.', $raw);
            return (int)round((float)$number * 1000000);
        }

        if(is_numeric($raw)){
            $value = (float)$raw;
            if($value > 0 && $value < 10){
                return (int)round($value * 1000000);
            }
            if($value >= 10 && $value < 100){
                return (int)round(($value / 10) * 1000000);
            }

            return (int)$value;
        }

        return (int)preg_replace('/[^0-9]/', '', $raw);
    }
}
