<?php
require_once 'config.php';

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'signup':
        signup();
        break;
    case 'login':
        login();
        break;
    default:
        json_response(false, "Invalid action");
}

function signup() {
    global $conn;
    
    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['username']) || !isset($postData['email']) || !isset($postData['password'])) {
        json_response(false, "Please provide username, email and password");
    }
    
    $username = sanitize_input($postData['username']);
    $email = sanitize_input($postData['email']);
    $password = password_hash($postData['password'], PASSWORD_DEFAULT); // Encrypt password
    
    // Check if username or email already exists
    $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) > 0) {
        json_response(false, "Username or email already exists");
    }
    
    // Insert new user
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        json_response(true, "Registration successful", ['user_id' => $user_id, 'username' => $username]);
    } else {
        json_response(false, "Error: " . mysqli_error($conn));
    }
}

function login() {
    global $conn;
    
    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['username']) || !isset($postData['password'])) {
        json_response(false, "Please provide username and password");
    }
    
    $username = sanitize_input($postData['username']);
    $password = $postData['password'];
    
    // Get user from database
    $sql = "SELECT user_id, username, password FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $row['password'])) {
            // Password is correct
            json_response(true, "Login successful", [
                'user_id' => $row['user_id'],
                'username' => $row['username']
            ]);
        } else {
            json_response(false, "Invalid password");
        }
    } else {
        json_response(false, "User not found");
    }
}
?> 