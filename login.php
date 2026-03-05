<?php
// login.php
session_start();

$servername = "localhost";
$username   = "rsoa_rsoa311_2";
$password   = "123456";
$dbname     = "rsoa_rsoa311_2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $pass     = $_POST['password'];

    if (empty($username) || empty($pass)) {
        $error = "Username and password are required!";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $db_username, $hashed);
            $stmt->fetch();

            if (password_verify($pass, $hashed)) {
                $_SESSION['user_id']   = $id;
                $_SESSION['username']  = $db_username;
                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "No account found with that username!";
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
<title>Login - My Blog</title>
<style>
    body {font-family:Arial,sans-serif;background:linear-gradient(to right,#f0f2f5,#e0f7fa);margin:0;padding:20px;color:#333;}
    .container {max-width:450px;margin:100px auto;background:white;padding:40px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.15);}
    h2 {text-align:center;color:#4285f4;margin-bottom:30px;}
    label {display:block;margin:10px 0 5px;font-weight:bold;}
    input {width:100%;padding:12px;border:1px solid #ccc;border-radius:6px;font-size:1em;box-sizing:border-box;}
    button {width:100%;padding:14px;background:#4285f4;color:white;border:none;border-radius:6px;font-size:1.1em;cursor:pointer;margin-top:20px;transition:background 0.3s;}
    button:hover {background:#357ae8;}
    .error {color:#d32f2f;background:#ffebee;padding:10px;border-radius:6px;margin:10px 0;text-align:center;}
    .link {text-align:center;margin-top:20px;}
    .link a {color:#0f9d58;text-decoration:none;font-weight:bold;}
    .link a:hover {text-decoration:underline;}
    @media (max-width:500px) {.container {padding:25px;margin:60px auto;}}
</style>
</head>
<body>
<div class="container">
    <h2>Login to Your Blog</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <div class="link">Don't have an account? <a href="signup.php">Sign Up here</a></div>
</div>
</body>
</html>
<?php $conn->close(); ?>
