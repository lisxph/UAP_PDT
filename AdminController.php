<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DestinationModel.php';
require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/ReviewModel.php';

class AdminController extends Controller {
    protected $conn;
    protected $userModel;
    protected $destinationModel;
    protected $dashboardModel;
    protected $bookingModel;
    protected $paymentModel;
    protected $reviewModel;

    public function __construct($conn){
        $this->conn = $conn;
        $this->userModel = new UserModel($conn);
        $this->destinationModel = new DestinationModel($conn);
        $this->dashboardModel = new DashboardModel($conn);
        $this->bookingModel = new BookingModel($conn);
        $this->paymentModel = new PaymentModel($conn);
        $this->reviewModel = new ReviewModel($conn);
    }

    protected function requireAdmin()
{
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    $timeout = 300; // 5 menit

    // belum login
    if(!isset($_SESSION['user_id'])){
        header("Location: /wandee/auth/loginregister");
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

    // cek role admin
    if($_SESSION['role'] !== 'admin'){
        header("Location: /wandee/auth/loginregister");
        exit;
    }

    // update aktivitas terakhir
    $_SESSION['last_activity'] = time();

    return (int) $_SESSION['user_id'];
}

    public function dashboard(){
        $user_id = $this->requireAdmin();
        $user = $this->userModel->findById($user_id);
        $total_users = $this->userModel->countAll();
        $total_trips = $this->dashboardModel->countTrips();
        $total_bookings = $this->dashboardModel->countBookings();
        $total_payments = $this->dashboardModel->countPayments();
        
        $total_revenue = $this->dashboardModel->getTotalRevenue();
        $category_stats = $this->dashboardModel->getCategoryStats();
        $recent_bookings = $this->dashboardModel->getRecentBookings(5);
        $monthly_stats = $this->dashboardModel->getMonthlyBookingStats();

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function manage(){
        $user_id = $this->requireAdmin();
        $user = $this->userModel->findById($user_id);
        $destinations = $this->destinationModel->getAllOrderedById();

        require __DIR__ . '/../views/admin/manage.php';
    }

    public function manage_payments(){
        $user_id = $this->requireAdmin();
        $user = $this->userModel->findById($user_id);
        $filter = $_GET['filter'] ?? 'all';
        $payments = $this->paymentModel->getAllWithBooking($filter);

        require __DIR__ . '/../views/admin/manage_payments.php';
    }

    public function payment_update(){
    $this->requireAdmin();

    $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
    $status = $_POST['status'] ?? '';
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    $valid = ['verified', 'rejected'];

    if($payment_id && in_array($status, $valid, true)){

        $payment = $this->paymentModel->findById($payment_id);

        if($payment){

            $booking = $this->bookingModel->findById($payment['booking_id']);

            // booking sudah auto-cancel
            if(
                $booking &&
                ($booking['trip_status'] ?? '') === 'cancelled'
            ){
                header('Location: /wandee/admin/manage_payments');
                exit;
            }

            $this->paymentModel->updateStatus(
                $payment_id,
                $status,
                $rejection_reason
            );

            $this->bookingModel->updatePaymentStatus(
                $payment['booking_id'],
                $status === 'verified' ? 'paid' : 'pending'
            );

            if($status === 'verified'){
                $this->bookingModel->updateTripStatus(
                    $payment['booking_id'],
                    'ongoing'
                );
            }
        }
    }

    header('Location: /wandee/admin/manage_payments');
    exit;
}

    public function trip_complete(){
        $this->requireAdmin();
        $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        if($booking_id > 0){
            $this->bookingModel->updateTripStatus($booking_id, 'completed');
        }

        header('Location: /wandee/admin/manage_payments');
        exit;
    }

    public function manage_reviews(){
        $user_id = $this->requireAdmin();
        $user = $this->userModel->findById($user_id);
        $reviews = $this->reviewModel->getAllWithUserDestination();

        require __DIR__ . '/../views/admin/manage_reviews.php';
    }

    public function review_delete(){
        $this->requireAdmin();
        $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        if($review_id > 0){
            $review = $this->reviewModel->findById($review_id);
            $this->reviewModel->deleteById($review_id);
            if($review){
                $avgRating = $this->reviewModel->averageRatingByDestination($review['destination_id']);
                if($avgRating !== null){
                    $this->destinationModel->updateRating($review['destination_id'], $avgRating);
                }
            }
        }

        header('Location: /wandee/admin/manage_reviews');
        exit;
    }

    public function edit_destination(){
        $this->requireAdmin();
        $id = $_GET['id'] ?? 0;
        $data = $this->destinationModel->findById($id);

        if(!$data){
            header('Location: /wandee/admin/manage');
            exit;
        }

        require __DIR__ . '/../views/admin/edit_destination.php';
    }

    public function profile(){
        $user_id = $this->requireAdmin();
        $user = $this->userModel->findById($user_id);

        require __DIR__ . '/../views/admin/profile.php';
    }
}
