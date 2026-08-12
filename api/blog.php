<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// GET /api/blog.php?action=list&page=1
// GET /api/blog.php?action=single&id=5
// POST /api/blog.php (create) -> expects {title, content}
// PUT /api/blog.php?id=5 (update) -> expects {title, content}
// DELETE /api/blog.php?id=5

$action = $_GET['action'] ?? '';

// Public endpoints: list and single
if ($method === 'GET' && $action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $countResult = $conn->query("SELECT COUNT(*) as total FROM blogPost");
    $total = $countResult->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT b.id, b.title, b.content, b.created_at, b.updated_at, u.username as author
        FROM blogPost b
        JOIN user u ON b.user_id = u.id
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $blogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['blogs' => $blogs, 'total' => $total, 'page' => $page]);
    exit;
}

if ($method === 'GET' && $action === 'single' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("
        SELECT b.id, b.title, b.content, b.created_at, b.updated_at, u.username as author, u.id as user_id
        FROM blogPost b
        JOIN user u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $blog = $stmt->get_result()->fetch_assoc();
    if ($blog) {
        echo json_encode($blog);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Blog not found']);
    }
    exit;
}

// Protected endpoints (require login)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// CREATE
if ($method === 'POST' && $action === 'create') {
    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';
    if (empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and content required']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO blogPost (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $title, $content);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Blog created', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// UPDATE (PUT) - only if owned
if ($method === 'PUT' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';

    // Check ownership
    $check = $conn->prepare("SELECT user_id FROM blogPost WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $blog = $result->fetch_assoc();
    if (!$blog || $blog['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only edit your own blogs']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE blogPost SET title = ?, content = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Blog updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed']);
    }
    $stmt->close();
    exit;
}

// DELETE - only if owned
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $check = $conn->prepare("SELECT user_id FROM blogPost WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $blog = $result->fetch_assoc();
    if (!$blog || $blog['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only delete your own blogs']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM blogPost WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Blog deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Deletion failed']);
    }
    $stmt->close();
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>