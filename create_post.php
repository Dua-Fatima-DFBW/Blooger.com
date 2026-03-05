<?php
// create_post.php - Create New Blog Post (English version)
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username   = "rsoa_rsoa311_2";
$password   = "123456";
$dbname     = "rsoa_rsoa311_2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$success = false;
$image_path = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title    = trim($_POST['title'] ?? '');
    $content  = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? '';
    $author   = $_SESSION['username'];  // Use logged-in username

    // Image upload handling
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, JPEG, PNG, or GIF files are allowed.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB limit
            $errors[] = "Image size must be less than 2MB.";
        } else {
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $target = "uploads/" . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = $target;
            } else {
                $errors[] = "Image upload failed. Check 'uploads/' folder permissions (755 or 777).";
            }
        }
    }

    // Basic validation
    if (empty($title) || empty($content) || empty($category)) {
        $errors[] = "Title, Content, and Category are required!";
    }

    if (empty($errors)) {
        $excerpt = mb_substr(strip_tags($content), 0, 200) . (mb_strlen(strip_tags($content)) > 200 ? '...' : '');

        $sql = "INSERT INTO posts (user_id, title, content, excerpt, author, category, image_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $user_id = $_SESSION['user_id'];

        $stmt->bind_param("issssss", $user_id, $title, $content, $excerpt, $author, $category, $image_path);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create New Post</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(to bottom right, #f0f4f8, #e0f2fe);
        margin: 0;
        padding: 20px;
        color: #333;
    }
    .form-container {
        max-width: 900px;
        margin: 30px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    h2 {
        text-align: center;
        color: #0f9d58;
        margin-bottom: 25px;
    }
    label {
        display: block;
        margin: 12px 0 5px;
        font-weight: bold;
    }
    input[type=text], select, input[type=file] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 1rem;
    }
    #editor {
        border: 1px solid #ccc;
        min-height: 280px;
        padding: 15px;
        border-radius: 6px;
        background: #fff;
        margin-bottom: 15px;
    }
    .toolbar button {
        padding: 8px 14px;
        background: #4285f4;
        color: white;
        border: none;
        border-radius: 5px;
        margin-right: 6px;
        cursor: pointer;
    }
    .toolbar button:hover { background: #3367d6; }
    #preview {
        max-width: 100%;
        max-height: 280px;
        margin: 15px 0;
        border-radius: 8px;
        display: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    button[type=submit] {
        width: 100%;
        padding: 14px;
        background: #0f9d58;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 1.1rem;
        cursor: pointer;
        margin-top: 20px;
    }
    button[type=submit]:hover { background: #0b8043; }
    .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin: 10px 0; }
    .success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; }
    .back-link { text-align: center; margin-top: 20px; }
    .back-link a { color: #4285f4; font-weight: bold; text-decoration: none; }
</style>
</head>
<body>

<div class="form-container">
    <h2>Create New Blog Post</h2>

    <?php if ($success): ?>
        <div class="success">
            Post published successfully! 🎉<br><br>
            <a href="index.php">Go to Homepage</a> | 
            <a href="create_post.php">Write Another Post</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" required>

        <label for="category">Category *</label>
        <select id="category" name="category" required>
            <option value="">-- Select Category --</option>
            <option value="Technology">Technology</option>
            <option value="Lifestyle">Lifestyle</option>
            <option value="Business">Business</option>
            <option value="Travel">Travel</option>
        </select>

        <label>Featured Image (optional – JPG, PNG, GIF | max 2MB)</label>
        <input type="file" name="image" accept="image/*" id="imageInput">
        <img id="preview" alt="Image Preview">

        <label>Content *</label>
        <div class="toolbar">
            <button type="button" onclick="formatText('bold')">Bold</button>
            <button type="button" onclick="formatText('italic')">Italic</button>
            <button type="button" onclick="formatText('underline')">Underline</button>
            <button type="button" onclick="formatText('insertUnorderedList')">Bullet List</button>
            <button type="button" onclick="formatText('insertOrderedList')">Numbered List</button>
        </div>
        <div id="editor" contenteditable="true"></div>
        <input type="hidden" name="content" id="hiddenContent">

        <button type="submit" onclick="document.getElementById('hiddenContent').value = document.getElementById('editor').innerHTML;">
            Publish Post
        </button>
    </form>
    <?php endif; ?>

    <div class="back-link">
        <a href="index.php">← Back to Homepage</a>
    </div>
</div>

<script>
function formatText(command) {
    document.execCommand(command, false, null);
}

document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const preview = document.getElementById('preview');
            preview.src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>
<?php $conn->close(); ?>
