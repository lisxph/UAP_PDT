<?php
require_once __DIR__ . '/../models/DestinationModel.php';
require_once __DIR__ . '/../models/FavoriteModel.php';
require_once __DIR__ . '/../models/ReviewModel.php';

class HomeController {
    protected $conn;
    protected $destModel;
    protected $favoriteModel;
    protected $reviewModel;

    public function __construct($conn){
        $this->conn = $conn;
        $this->destModel = new DestinationModel($conn);
        $this->favoriteModel = new FavoriteModel($conn);
        $this->reviewModel = new ReviewModel($conn);
    }

    public function index(){
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(($_SESSION['role'] ?? '') === 'admin'){
            header('Location: /wandee/admin/dashboard');
            exit;
        }

        $destinations = $this->destModel->getAllOrderedByRating();
        $destinationIds = array_map(function($dest){
            return (int)$dest['id'];
        }, $destinations);
        $ratingStats = $this->reviewModel->getRatingStatsByDestinationIds($destinationIds);
        $favoriteDestinationIds = isset($_SESSION['user_id'])
            ? $this->favoriteModel->getDestinationIdsByUser((int)$_SESSION['user_id'])
            : [];
        require __DIR__ . '/../views/home.php';
    }
}
