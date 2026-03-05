<?php
// view_post.php - Single Post View with Image, Comments, Related Posts
session_start();

$servername = "localhost";
$username   = "rsoa_rsoa311_2";
$password   = "123456";
$dbname     = "rsoa_rsoa311_2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid post ID.");
}

// Fetch the post
$sql = "SELECT * FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    die("Post not found.");
}

// Handle new comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_comment'])) {
    $comment_content = trim($_POST['comment_content']);
    $comment_author  = isset($_SESSION['username']) ? $_SESSION['username'] : trim($_POST['comment_author']);

    if (!empty($comment_content) && !empty($comment_author)) {
        $sql = "INSERT INTO comments (post_id, author, content, user_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
        $stmt->bind_param("issi", $id, $comment_author, $comment_content, $user_id);
        $stmt->execute();
        $stmt->close();
        header("Location: view_post.php?id=$id#comments"); // refresh to show new comment
        exit;
    }
}

// Fetch comments
$sql = "SELECT * FROM comments WHERE post_id = ? ORDER BY comment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$comments_result = $stmt->get_result();
$stmt->close();

// Fetch related posts (same category, not current, limit 3)
$sql = "SELECT id, title FROM posts 
        WHERE category = ? AND id != ? 
        ORDER BY publish_date DESC LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $post['category'], $id);
$stmt->execute();
$related_result = $stmt->get_result();
$stmt->close();

// Check if current user owns this post (for edit/delete buttons)
$is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($post['title']) ?> - My Blog</title>
<style>
    :root {
        --primary: #4285f4;
        --success: #0f9d58;
        --danger:  #db4437;
        --light:   #f0f2f5;
    }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: linear-gradient(135deg, var(--light), #e3f2fd);
        margin: 0;
        color: #333;
        line-height: 1.7;
    }
    .container {
        max-width: 900px;
        margin: 30px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    h1 {
        color: var(--primary);
        margin-top: 0;
        font-size: 2.2rem;
    }
    .meta {
        color: #666;
        font-size: 0.95rem;
        margin: 10px 0 25px;
    }
    .post-image {
        width: 100%;
        max-height: 450px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .content {
        font-size: 1.05rem;
    }
    .content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 15px 0;
    }
    .related {
        margin: 40px 0;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .related h3 {
        color: var(--primary);
    }
    .related-list {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    .related-item {
        flex: 1 1 200px;
        background: #f9f9f9;
        padding: 12px;
        border-radius: 8px;
    }
    .related-item a {
        color: #333;
        text-decoration: none;
        font-weight: 500;
    }
    .related-item a:hover {
        color: var(--primary);
    }
    .comments {
        margin: 40px 0;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .comments h3 {
        color: var(--primary);
    }
    .comment {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .comment-author {
        font-weight: bold;
        color: #444;
    }
    .comment-date {
        font-size: 0.85rem;
        color: #888;
    }
    .comment-form {
        margin-top: 25px;
    }
    .comment-form textarea {
        width: 100%;
        min-height: 100px;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        resize: vertical;
    }
    .comment-form input[type=text] {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        margin-right: 10px;
        transition: 0.3s;
    }
    .btn-edit  { background: #f4b400; }
    .btn-edit:hover  { background: #e0a800; }
    .btn-delete { background: var(--danger); }
    .btn-delete:hover { background: #c62828; }
    .btn-home   { background: #555; }
    .btn-home:hover   { background: #333; }
    .actions {
        text-align: center;
        margin: 30px 0;
    }
    @media (max-width: 768px) {
        .container { padding: 20px; margin: 20px; }
        .related-list { flex-direction: column; }
    }
</style>
</head>
<body>

<div class="container">

    <?php if (!empty($post['image_path'])): ?>
        <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="post-image">
    <?php endif; ?>

    <h1><?= htmlspecialchars($post['title']) ?></h1>

    <div class="meta">
        By <strong><?= htmlspecialchars($post['author']) ?></strong> 
        • <?= date('d M Y - h:i A', strtotime($post['publish_date'])) ?> 
        • <?= htmlspecialchars($post['category']) ?>
    </div>

    <div class="content">
        <?= $post['content'] ?>
    </div>

    <!-- Related Posts -->
    <?php if ($related_result->num_rows > 0): ?>
    <div class="related">
        <h3>Related Posts</h3>
        <div class="related-list">
            <?php while ($rel = $related_result->fetch_assoc()): ?>
                <div class="related-item">
                    <a href="view_post.php?id=<?= $rel['id'] ?>">
                        <?= htmlspecialchars($rel['title']) ?>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Comments Section -->
    <div class="comments" id="comments">
        <h3>Comments (<?= $comments_result->num_rows ?>)</h3>

        <?php if ($comments_result->num_rows > 0): ?>
            <?php while ($comment = $comments_result->fetch_assoc()): ?>
                <div class="comment">
                    <div class="comment-author"><?= htmlspecialchars($comment['author']) ?></div>
                    <div class="comment-date"><?= date('d M Y - h:i A', strtotime($comment['comment_date'])) ?></div>
                    <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No comments yet. Be the first to comment!</p>
        <?php endif; ?>

        <!-- Add Comment Form -->
        <div class="comment-form">
            <form method="POST">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <input type="text" name="comment_author" placeholder="Your Name" required>
                <?php endif; ?>
                <textarea name="comment_content" placeholder="Write your comment here..." required></textarea>
                <button type="submit" name="add_comment" class="btn" style="background:#0f9d58;">Post Comment</button>
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="actions">
        <?php if ($is_owner): ?>
            <a href="edit_post.php?id=<?= $id ?>" class="btn btn-edit">Edit Post</a>
            <a href="edit_post.php?id=<?= $id ?>" onclick="return confirm('Delete this post permanently?');" class="btn btn-delete">Delete Post</a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-home">Back to Home</a>
    </div>

</div>

</body>
</html>
<?php $conn->close(); ?>
