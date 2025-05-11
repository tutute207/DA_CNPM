<?php
session_start();
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qly_thuvien";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT username, role FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] == 3) {
            header("Location: trangchu.php");
        } else if ($user['role'] == 2 || $user['role'] == 1) {
            header("Location: ../thuthu_admin/index.php");
        }
        exit();
    } else {
        $error_message = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Frame</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }
        .container {
            display: flex;
            width: 100%;
            height: 100%;
        }
        .left-section {
            flex: 1;
            background-color: #1a3c5e;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
        }
        .right-section {
            flex: 1;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .user-icon {
            width: 100px;
            height: 100px;
            border: 2px solid white;
            border-radius: 50%;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 50px;
            color: white;
        }
        .login-form {
            width: 300px;
            text-align: left;
        }
        .login-form label {
            display: block;
            margin-bottom: 5px;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: none;
            border-radius: 20px;
            background-color: #fff;
            font-size: 16px;
            box-sizing: border-box;
        }
        .login-form button {
            width: 100%;
            padding: 10px;
            background-color: #f28c38;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .login-form button:hover {
            background-color: #e07b30;
        }
        .logo-placeholder {
            width: 200px;
            height: 200px;
            background-color: #ccc;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            color: #333;
            text-align: center;
        }
        .logo {
            width: 250px;
            height: auto;
            margin: 20px;
        }
        .right-section {
            background-color: bisque;
        }
        .login-form a {
            display: block;
            margin-top: 10px;
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="user-icon">👤</div>
            <div class="login-form">
                <form method="post" action="">
                    <label for="username">Name</label>
                    <input type="text" id="username" name="username" placeholder="Email@ensia.edu.dz" required>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="submit">Đăng Nhập</button>
                </form>
        <?php if (!empty($error_message)): ?>
            <p class="message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
                <a href="dangky.php">Đăng Ký</a>
            </div>
        </div>
        <div class="right-section">
            <div class="logo-placeholder">
                <img src="../image/logo_sach.png" alt="Logo" class="logo">
            </div>
        </div>
    </div>
</body>
</html>