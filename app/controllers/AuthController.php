<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController extends Controller {
    protected $conn;
    protected $userModel;

    public function __construct($conn){
        $this->conn = $conn;
        $this->userModel = new UserModel($conn);
    }

    public function register(){
        if(session_status() === PHP_SESSION_NONE) session_start();

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = md5($_POST['password'] ?? '');

        if($this->userModel->findByEmail($email)){
            echo "Email sudah digunakan";
            exit;
        }

        $this->userModel->create($name, $email, $password);
        header("Location: /wandee/auth/loginregister");
        exit;
    }

    public function index(){
        if(session_status() === PHP_SESSION_NONE) session_start();
        require __DIR__ . '/../views/auth.php';
    }

    public function login(){
        if(session_status() === PHP_SESSION_NONE) session_start();

        $email = $_POST['email'] ?? '';
        $password = md5($_POST['password'] ?? '');

        $user = $this->userModel->findByEmail($email);

        if($user && $user['password'] === $password){

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // SESSION ACTIVITY
            $_SESSION['last_activity'] = time();

            if($user['role'] == 'admin'){
                header("Location: /wandee/admin/dashboard");
                exit;
            } else {
                header("Location: /wandee/user/index");
                exit;
            }
        } else {
            header("Location: /wandee/auth/loginregister?error=credentials");
            exit;
        }
    }

    public function logout(){
        if(session_status() === PHP_SESSION_NONE) session_start();
        // clear all session variables
        $_SESSION = [];

        // delete session cookie if used
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }

        // destroy session
        session_unset();
        session_destroy();

        // redirect to landing page
        header("Location: /wandee");
        exit;
    }
}
