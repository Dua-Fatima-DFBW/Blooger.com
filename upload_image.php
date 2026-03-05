<?php
// upload_image.php  → sirf image upload wali file

$upload_dir = "uploads/";
$max_size   = 2 * 1024 * 1024; // 2 MB
$allowed    = ['jpg', 'jpeg', 'png', 'gif'];

$message    = "";
$uploaded_file = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {

    $file = $_FILES["image"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error: " . $file["error"];
    } else {

        $file_name = basename($file["name"]);
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $file["size"];

        // checks
        if (!in_array($file_ext, $allowed)) {
            $message = "Sirf JPG, JPEG, PNG ya GIF allowed hain.";
        } elseif ($file_size > $max_size) {
            $message = "File size 2MB se zyada hai.";
        } else {

            // unique name banao taake overwrite na ho
            $new_name      = time() . "_" . uniqid() . "." . $file_ext;
            $target_path   = $upload_dir . $new_name;

            if (move_uploaded_file($file["tmp_name"], $target_path)) {
                $message = "Image successfully upload ho gayi!";
                $uploaded_file = $target_path;
            } else {
                $message = "Upload fail ho gaya – folder permissions check karo (uploads/ ko 755 ya 777 banao).";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload - Sirf Yeh</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #e0f7fa, #ffffff);
            margin: 0;
            padding: 30px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        h2 {
            color: #0f9d58;
            text-align: center;
        }
        .upload-box {
            border: 2px dashed #4285f4;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            background: #f8fbff;
        }
        input[type="file"] {
            margin: 15px 0;
        }
        button {
            background: #0f9d58;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1em;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: #0b8043;
        }
        .message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 6px;
            text-align: center;
        }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error   { background: #ffebee; color: #c62828; }
        .preview {
            max-width: 100%;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .back {
            text-align: center;
            margin-top: 25px;
        }
        .back a {
            color: #4285f4;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Sirf Image Upload</h2>

    <form method="post" enctype="multipart/form-data">
        <div class="upload-box">
            <p>Image choose karo (jpg, jpeg, png, gif)</p>
            <input type="file" name="image" accept="image/*" required>
            <br><br>
            <button type="submit">Upload Karo</button>
        </div>
    </form>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'success') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($uploaded_file): ?>
        <div style="text-align:center;">
            <p><strong>Uploaded Image:</strong></p>
            <img src="<?= htmlspecialchars($uploaded_file) ?>" alt="Uploaded" class="preview">
            <p style="margin-top:10px; color:#555;">
                Path: <code><?= htmlspecialchars($uploaded_file) ?></code>
            </p>
        </div>
    <?php endif; ?>

    <div class="back">
        <a href="index.php">← Wapas Homepage pe</a>
    </div>
</div>

</body>
</html>
