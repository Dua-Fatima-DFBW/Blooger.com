<?php
// index.php - Homepage (Updated with Login System)
session_start();

$servername = "localhost";
$username   = "rsoa_rsoa311_2";
$password   = "123456";
$dbname     = "rsoa_rsoa311_2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search and category filter
$search   = isset($_GET['search'])   ? $_GET['search']   : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$where = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $search = "%" . $conn->real_escape_string($search) . "%";
    $where .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types   .= "ss";
}

if ($category) {
    $category = $conn->real_escape_string($category);
    $where .= " AND category = ?";
    $params[] = $category;
    $types   .= "s";
}

$sql = "SELECT * FROM posts $where ORDER BY publish_date DESC LIMIT 12";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Blog - Home</title>
<style>
    :root {
        --primary: #4285f4;
        --success: #0f9d58;
        --danger:  #db4437;
        --warning: #f4b400;
        --light:   #f0f2f5;
        --dark:    #202124;
    }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: linear-gradient(135deg, var(--light), #e3f2fd);
        margin: 0;
        color: #333;
        line-height: 1.6;
    }
    header {
        background: var(--primary);
        color: white;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    header h1 {
        margin: 0;
        font-size: 2.4rem;
        letter-spacing: 1px;
    }
    .user-bar {
        background: rgba(255,255,255,0.95);
        padding: 0.8rem;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .user-bar a {
        color: var(--danger);
        font-weight: bold;
        text-decoration: none;
        margin-left: 1.5rem;
    }
    nav {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 1.5rem;
        padding: 1.5rem 0;
        background: white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    nav a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.3s;
    }
    nav a:hover {
        background: #e3f2fd;
        color: #0d47a1;
    }
    .search-bar {
        text-align: center;
        padding: 1.5rem;
    }
    .search-bar form {
        display: inline-flex;
        max-width: 500px;
        width: 90%;
    }
    .search-bar input {
        flex: 1;
        padding: 0.9rem 1.2rem;
        border: 1px solid #ddd;
        border-radius: 6px 0 0 6px;
        font-size: 1rem;
    }
    .search-bar button {
        padding: 0.9rem 1.8rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 0 6px 6px 0;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.3s;
    }
    .search-bar button:hover {
        background: #3367d6;
    }
    .posts {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.8rem;
    }
    .post-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .post-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }
    .post-card .content {
        padding: 1.5rem;
    }
    .post-card h2 {
        margin: 0 0 0.8rem;
        font-size: 1.4rem;
        color: var(--primary);
    }
    .post-card h2 a {
        color: inherit;
        text-decoration: none;
    }
    .post-card .excerpt {
        color: #555;
        margin: 0 0 1rem;
        font-size: 0.95rem;
    }
    .post-card .meta {
        color: #777;
        font-size: 0.9rem;
        font-style: italic;
    }
    .create-btn {
        text-align: center;
        margin: 2.5rem 0;
    }
    .create-btn a {
        display: inline-block;
        padding: 1rem 2.5rem;
        background: var(--success);
        color: white;
        text-decoration: none;
        font-size: 1.15rem;
        font-weight: bold;
        border-radius: 50px;
        box-shadow: 0 4px 12px rgba(15,157,88,0.3);
        transition: all 0.3s;
    }
    .create-btn a:hover {
        background: #0b8043;
        transform: translateY(-2px);
    }
    .no-posts {
        text-align: center;
        padding: 3rem;
        color: #777;
        font-size: 1.2rem;
    }
    @media (max-width: 768px) {
        .posts { grid-template-columns: 1fr; padding: 1rem; }
        header h1 { font-size: 2rem; }
        nav { gap: 1rem; padding: 1rem; }
    }
</style>
</head>
<body>

<header>
    <h1>My Blog</h1>
</header>

<?php if (isset($_SESSION['username'])): ?>
<div class="user-bar">
    Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> 
    <a href="logout.php">Logout</a>
</div>
<?php endif; ?>

<nav>
    <a href="?category=Technology">Technology</a>
    <a href="?category=Lifestyle">Lifestyle</a>
    <a href="?category=Business">Business</a>
    <a href="?category=Travel">Travel</a>
    <a href="?">All Posts</a>
</nav>

<div class="search-bar">
    <form method="GET">
        <input type="text" name="search" placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>
</div>

<div class="posts">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="post-card">
                <div class="content">
                    <h2>
                        <a href="#" onclick="goToPost(<?= $row['id'] ?>); return false;">
                            <?= htmlspecialchars($row['title']) ?>
                        </a>
                    </h2>
                    <p class="excerpt"><?= htmlspecialchars($row['excerpt']) ?></p>
                    <p class="meta">
                        By <?= htmlspecialchars($row['author']) ?> • 
                        <?= date('d M Y', strtotime($row['publish_date'])) ?>
                    </p>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-posts">No posts found. <?= isset($_SESSION['user_id']) ? 'Be the first to write one!' : 'Login and start writing!' ?></div>
    <?php endif; ?>
</div>

<div class="create-btn">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="create_post.php">+ Write New Post</a>
    <?php else: ?>
        <a href="login.php">Login to Write a Post</a>
    <?php endif; ?>
</div>

<script>
function goToPost(id) {
    window.location.href = 'view_post.php?id=' + id;
}
</script>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>
