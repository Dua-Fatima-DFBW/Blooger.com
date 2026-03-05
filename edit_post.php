<?php
// edit_post.php - Edit & Delete Post
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username   = "rsoa_rsoa311_2";
$password   = "123456";
$dbname     = "rsoa_rsoa311_2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid post ID.");

// Fetch post
$sql = "SELECT * FROM posts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) die("Post not found.");

// Check ownership
if ($post['user_id'] != $_SESSION['user_id']) {
    die("You are not authorized to edit this post.");
}

$errors = [];
$success = false;
$image_path = $post['image_path'];  // keep existing by default

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['delete'])) {
        // Delete post
        $sql = "DELETE FROM posts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Optional: delete image file from server
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Update post
        $title    = trim($_POST['title']);
        $content  = $_POST['content'];
        $category = $_POST['category'];
        $author   = $_SESSION['username'];

        // Image handling (replace or keep old)
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_size = $_FILES['image']['size'];

            if (!in_array($file_ext, $allowed)) {
                $errors[] = "Only JPG, JPEG, PNG, GIF allowed.";
            } elseif ($file_size > 2097152) {
                $errors[] = "Image size max 2MB.";
            } else {
                $new_name = uniqid() . '.' . $file_ext;
                $upload_path = 'uploads/' . $new_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $upload_path;
                } else {
                    $errors[] = "Image upload failed. Check folder permissions.";
                }
            }
        }

        if (empty($title) || empty($content) || empty($category)) {
            $errors[] = "Title, content and category are required.";
        }

        if (empty($errors)) {
            $excerpt = substr(strip_tags($content), 0, 200) . (strlen(strip_tags($content)) > 200 ? '...' : '');

            $sql = "UPDATE posts SET 
                    title = ?, content = ?, excerpt = ?, 
                    author = ?, category = ?, image_path = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $title, $content, $excerpt, $author, $category, $image_path, $id);

            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Update failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Post - <?= htmlspecialchars($post['title']) ?></title>
<style>
    body {font-family:Arial,sans-serif;background:linear-gradient(to right,#f0f2f5,#fff3e0);margin:0;padding:20px;color:#333;}
    .container {max-width:900px;margin:30px auto;background:white;padding:30px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);}
    h2 {color:#f4b400;text-align:center;margin-bottom:25px;}
    label {display:block;margin:12px 0 6px;font-weight:bold;}
    input[type=text], select {width:100%;padding:12px;border:1px solid #ccc;border-radius:6px;font-size:1em;box-sizing:border-box;}
    #editor {border:1px solid #ccc;min-height:300px;padding:15px;border-radius:6px;background:#fff;margin-bottom:15px;}
    .toolbar button {padding:8px 14px;background:#4285f4;color:white;border:none;border-radius:4px;margin-right:6px;cursor:pointer;}
    .toolbar button:hover {background:#3367d6;}
    #current-image {max-width:100%;max-height:250px;margin:15px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
    #image-preview {max-width:100%;max-height:250px;margin:15px 0;border-radius:8px;display:none;}
    button[type=submit] {width:100%;padding:14px;background:#4285f4;color:white;border:none;border-radius:6px;font-size:1.1em;cursor:pointer;margin-top:20px;}
    button[type=submit]:hover {background:#3367d6;}
    .delete-btn {background:#db4437;margin-top:10px;}
    .delete-btn:hover {background:#c62828;}
    .error {background:#ffebee;color:#c62828;padding:10px;border-radius:6px;margin:10px 0;}
    .success {background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:6px;text-align:center;font-weight:bold;}
    .back {text-align:center;margin-top:25px;}
    .back a {color:#0f9d58;text-decoration:none;font-weight:bold;}
    @media (max-width:768px) {.container {padding:20px;margin:20px;}}
</style>
</head>
<body>

<div class="container">
    <h2>Edit Your Post</h2>

    <?php if ($success): ?>
        <div class="success">Post updated successfully!<br><br>
            <a href="view_post.php?id=<?= $id ?>">View Post</a> | 
            <a href="index.php">Back to Home</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <?php foreach($errors as $err): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Post Title *</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>

        <label for="category">Category *</label>
        <select id="category" name="category" required>
            <option value="Technology" <?= $post['category']=='Technology'?'selected':'' ?>>Technology</option>
            <option value="Lifestyle"   <?= $post['category']=='Lifestyle'  ?'selected':'' ?>>Lifestyle</option>
            <option value="Business"    <?= $post['category']=='Business'   ?'selected':'' ?>>Business</option>
            <option value="Travel"      <?= $post['category']=='Travel'     ?'selected':'' ?>>Travel</option>
        </select>

        <label>Featured Image (leave empty to keep current)</label>
        <?php if ($image_path): ?>
            <p>Current Image:</p>
            <img id="current-image" src="<?= htmlspecialchars($image_path) ?>" alt="Current">
        <?php endif; ?>
        <input type="file" name="image" id="image" accept="image/*">
        <img id="image-preview" alt="New Preview">

        <label>Content *</label>
        <div class="toolbar">
            <button type="button" onclick="format('bold')">Bold</button>
            <button type="button" onclick="format('italic')">Italic</button>
            <button type="button" onclick="format('underline')">Underline</button>
            <button type="button" onclick="format('insertUnorderedList')">• List</button>
            <button type="button" onclick="format('insertOrderedList')">1. List</button>
        </div>
        <div id="editor" contenteditable="true"><?= $post['content'] ?></div>
        <input type="hidden" name="content" id="content">

        <button type="submit" onclick="document.getElementById('content').value = document.getElementById('editor').innerHTML;">Update Post</button>
        <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Are you sure you want to delete this post? This cannot be undone.');">Delete Post</button>
    </form>
    <?php endif; ?>

    <div class="back">
        <a href="view_post.php?id=<?= $id ?>">← Back to Post View</a> | 
        <a href="index.php">Home</a>
    </div>
</div>

<script>
function format(cmd) {
    document.execCommand(cmd, false, null);
}

document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const preview = document.getElementById('image-preview');
            preview.src = ev.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>
<?php $conn->close(); ?>
