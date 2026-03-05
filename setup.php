<?php
// setup.php - Run this once to create database tables
$servername = "localhost"; // Assuming local server, change if needed
$username = "rsoa_rsoa311_2";
$password = "123456";
$dbname = "rsoa_rsoa311_2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create posts table
$sql = "CREATE TABLE IF NOT EXISTS posts (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(500) NOT NULL,
    author VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    publish_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Posts table created successfully<br>";
} else {
    echo "Error creating posts table: " . $conn->error . "<br>";
}

// Create comments table
$sql = "CREATE TABLE IF NOT EXISTS comments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT(6) UNSIGNED NOT NULL,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    comment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Comments table created successfully<br>";
} else {
    echo "Error creating comments table: " . $conn->error . "<br>";
}

$conn->close();
?>
