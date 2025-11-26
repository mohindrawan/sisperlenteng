<?php
session_start();
function is_logged_in(){
    return isset($_SESSION['user']);
}
function is_admin(){
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}
function is_kelompok(){
    return is_logged_in() && $_SESSION['user']['role'] === 'kelompok';
}


