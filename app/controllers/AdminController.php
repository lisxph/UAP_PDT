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
        $validBookings = $this->dashboardModel->getValidBookings();
        $activityLog   = $this->dashboardModel->getActivityLog();
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

    // normalize status and validate
    $status = strtolower(trim($status));
    $valid = ['verified', 'rejected'];

    // temporary log for debugging unexpected status changes
    $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wandee_payment_update.log';
    $msg = sprintf("[%s] payment_update called - payment_id=%s status=%s rejection_reason=%s\n", date('c'), var_export($payment_id, true), var_export($status, true), substr($rejection_reason, 0, 200));
    error_log($msg, 3, $logFile);

    if($payment_id && in_array($status, $valid, true)){

        $payment = $this->paymentModel->findById($payment_id);

        if($payment){
            // log current payment record before change
            error_log(sprintf("[%s] existing payment status=%s, booking_id=%s\n", date('c'), ($payment['payment_status'] ?? ''), ($payment['booking_id'] ?? '')) , 3, $logFile);

            $booking = $this->bookingModel->findById($payment['booking_id']);

            // booking sudah auto-cancel
            if(
                $booking &&
                ($booking['trip_status'] ?? '') === 'cancelled'
            ){
                header('Location: /wandee/admin/manage_payments');
                exit;
            }

            if ($status === 'verified') {
                $resVerify = $this->paymentModel->verifyPayment($payment_id);
                error_log(sprintf("[%s] verifyPayment result=%s\n", date('c'), var_export((bool)$resVerify, true)), 3, $logFile);

                $this->bookingModel->updatePaymentStatus(
                    $payment['booking_id'],
                    'paid'
                );

                $this->bookingModel->updateTripStatus(
                    $payment['booking_id'],
                    'ongoing'
                );
            } else {
                $resUpdate = $this->paymentModel->updateStatus($payment_id, 'rejected', $rejection_reason);
                error_log(sprintf("[%s] updateStatus result=%s\n", date('c'), var_export((bool)$resUpdate, true)), 3, $logFile);

                $this->bookingModel->updatePaymentStatus(
                    $payment['booking_id'],
                    'pending'
                );

                if ($booking && ($booking['trip_status'] ?? '') === 'ongoing') {
                    $this->bookingModel->updateTripStatus(
                        $payment['booking_id'],
                        'new'
                    );
                }
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
