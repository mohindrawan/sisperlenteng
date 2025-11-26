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

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field() {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES);
    return '<input type="hidden" name="_csrf" value="' . $t . '">';
}

function validate_csrf($token) {
    if (empty($_SESSION['_csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['_csrf_token'], $token);
}


