<?php

session_start();

if(!isset($_SESSION['user_id'])){

    header("Location: /wandee/auth/loginregister");
    exit;

}

?>
