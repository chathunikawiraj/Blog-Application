<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

// REGISTER 
if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields required']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Registration successful']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Username or email already exists']);
    }
    $stmt->close();
    exit;
}

// LOGIN
if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode([
            'message' => 'Login successful',
            'id' => $user['id'],           // ✅ NOW INCLUDED
            'username' => $user['username']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
    $stmt->close();
    exit;
}

// LOGOUT
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['message' => 'Logged out']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>