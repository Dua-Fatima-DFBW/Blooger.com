<?php
// signup.php
$servername = "localhost";
$username   = "rsoa_rsoa311_2";
$password   = "123456";
$dbname     = "rsoa_rsoa311_2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $pass     = $_POST['password'];
    $pass2    = $_POST['password2'];

    if (empty($username) || empty($email) || empty($pass)) {
        $errors[] = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    } elseif ($pass !== $pass2) {
        $errors[] = "Passwords do not match!";
    } elseif (strlen($pass) < 6) {
        $errors[] = "Password must be at least 6 characters!";
    } else {
        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username or email already taken!";
        }
        $stmt->close();

        if (empty($errors)) {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Something went wrong. Try again.";
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
<title>Sign Up - My Blog</title>
<style>
    body {font-family:Arial,sans-serif;background:linear-gradient(to right,#f0f2f5,#e0f7fa);margin:0;padding:20px;color:#333;}
    .container {max-width:450px;margin:60px auto;background:white;padding:40px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.15);}
    h2 {text-align:center;color:#4285f4;margin-bottom:30px;}
    label {display:block;margin:10px 0 5px;font-weight:bold;}
    input {width:100%;padding:12px;border:1px solid #ccc;border-radius:6px;font-size:1em;box-sizing:border-box;}
    button {width:100%;padding:14px;background:#0f9d58;color:white;border:none;border-radius:6px;font-size:1.1em;cursor:pointer;margin-top:20px;transition:background 0.3s;}
    button:hover {background:#0b8043;}
    .error {color:#d32f2f;background:#ffebee;padding:10px;border-radius:6px;margin:10px 0;}
    .success {color:#2e7d32;background:#e8f5e9;padding:10px;border-radius:6px;margin:10px 0;text-align:center;}
    .link {text-align:center;margin-top:20px;}
    .link a {color:#4285f4;text-decoration:none;font-weight:bold;}
    .link a:hover {text-decoration:underline;}
    @media (max-width:500px) {.container {padding:25px;margin:40px auto;}}
</style>
</head>
<body>
<div class="container">
    <h2>Create Your Account</h2>

    <?php if ($success): ?>
        <div class="success">Registration successful!<br><br><a href="login.php">Click here to Login</a></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="password2">Confirm Password</label>
        <input type="password" id="password2" name="password2" required>

        <button type="submit">Sign Up</button>
    </form>
    <?php endif; ?>

    <div class="link">Already have an account? <a href="login.php">Login here</a></div>
</div>
</body>
</html>
<?php $conn->close(); ?>
