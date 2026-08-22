<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

$action = $_GET['action'] ?? '';


//HELPER FUNCTION: Clear all list cache files

function clearListCache() {
    $cacheFiles = glob('cache/blog_list_page_*.json');
    foreach ($cacheFiles as $file) {
        @unlink($file);
    }
}


// PUBLIC: LIST BLOGS (with caching)

if ($method === 'GET' && $action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    
    // Cache the list by page
    $cacheFile = "cache/blog_list_page_{$page}.json";
    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
        header('Content-Type: application/json');
        echo file_get_contents($cacheFile);
        exit;
    }
    
    $limit = 3;
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

    foreach ($blogs as &$blog) {
        preg_match('/!\[.*?\]\((.*?)\)/', $blog['content'], $matches);
        $blog['thumbnail'] = $matches[1] ?? 'default-image.jpg';
        //For local: Keep '/blog-app/' prefix
        // For live: Remove it
        $blog['thumbnail'] = str_replace('/blog-app/', '/', $blog['thumbnail']);        
        $plainText = strip_tags($blog['content']);
        $blog['excerpt'] = substr($plainText, 0, 150) . '...';
        $blog['title'] = htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8');
        $blog['content'] = htmlspecialchars($blog['content'], ENT_QUOTES, 'UTF-8');
        $blog['author'] = htmlspecialchars($blog['author'], ENT_QUOTES, 'UTF-8');
        $blog['excerpt'] = htmlspecialchars($blog['excerpt'], ENT_QUOTES, 'UTF-8');
        $blog['thumbnail'] = htmlspecialchars($blog['thumbnail'], ENT_QUOTES, 'UTF-8');
    }
    
    $response = json_encode(['blogs' => $blogs, 'total' => $total, 'page' => $page]);
    
    if (!is_dir('cache')) {
        mkdir('cache', 0777, true);
    }
    file_put_contents($cacheFile, $response);
    
    echo $response;
    exit;
}


// PUBLIC: SINGLE BLOG (with caching)

if ($method === 'GET' && $action === 'single' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $cacheFile = "cache/blog_{$id}.json";
    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
        header('Content-Type: application/json');
        echo file_get_contents($cacheFile);
        exit;
    }
    
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
        $blog['title']   = htmlspecialchars($blog['title'], ENT_QUOTES, 'UTF-8');
        $blog['content'] = htmlspecialchars($blog['content'], ENT_QUOTES, 'UTF-8');
        $blog['author']  = htmlspecialchars($blog['author'], ENT_QUOTES, 'UTF-8');
        
        if (!is_dir('cache')) {
            mkdir('cache', 0777, true);
        }
        file_put_contents($cacheFile, json_encode($blog));
        
        echo json_encode($blog);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Blog not found']);
    }
    exit;
}


// PROTECTED ENDPOINTS (require login)

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
        // Clear all list caches so the new blog appears
        clearListCache();
        echo json_encode(['message' => 'Blog created', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}


// UPDATE (PUT)
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
        //Clear list caches so changes appear
        clearListCache();
        // Also delete the single blog cache
        @unlink("cache/blog_{$id}.json");
        echo json_encode(['message' => 'Blog updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed']);
    }
    $stmt->close();
    exit;
}


// DELETE
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
        // Clear list caches so the blog disappears
        clearListCache();
        // Also delete the single blog cache
        @unlink("cache/blog_{$id}.json");
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