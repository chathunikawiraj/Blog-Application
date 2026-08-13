<?php
// Connect to database
$conn = new mysqli('localhost', 'root', '', 'blog_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected!<br><br>";

// Show users
$result = $conn->query("SELECT * FROM user");
echo "<b>Users:</b><br>";
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Username: {$row['username']} | Email: {$row['email']}<br>";
}

echo "<br><b>Blog Posts:</b><br>";
$result = $conn->query("SELECT * FROM blogPost");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Title: {$row['title']} | User ID: {$row['user_id']}<br>";
}

$conn->close();
?>