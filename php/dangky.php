<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Frame</title>
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
            background-color:bisque;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .right-section {
            flex: 1;
            background-color: #1a3c5e;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
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
        .signup-form {
            width: 300px;
            text-align: left;
        }
        .signup-form label {
            display: block;
            margin-bottom: 5px;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
        }
        .signup-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: none;
            border-radius: 20px;
            background-color: #fff !important; /* Đảm bảo nền trắng */
            font-size: 16px;
            box-sizing: border-box;
            -webkit-appearance: none; /* Loại bỏ kiểu mặc định của trình duyệt */
            -moz-appearance: none;
            appearance: none;
        }
        .signup-form button {
            width: 100%;
            padding: 10px;
            background-color: #ff6200;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .signup-form button:hover {
            background-color: #e55a00;
        }
        .signup-form a {
            display: block;
            margin-top: 10px;
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
        }
        .signup-form a:hover {
            text-decoration: underline;
        }
        .logo {
            width: 300px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <img src="../image/logo_sach.png" alt="Logo" class="logo">
        </div>
        <div class="right-section">
            <div class="user-icon">👤</div>
            <div class="signup-form">
        <form action="" method="post" >
            <label for="username">Tên đăng nhập :</label> 
            <input type="text" name="username" value="" required><br>
            <label for="email">Email :</label>
            <input type="email" name="email" value="" required><br>
            <label for="phone">Số điện thoại :</label>
            <input type="text" name="phone" value="" required><br>
            <label for="password">Mật khẩu :</label>
            <input type="password" name="password" value="" required><br>
            <label for="confirm_password">Nhập lại mật khẩu :</label>
            <input type="password" name="confirm_password" value="" required><br>
            <button type="submit">Đăng ký </button>
            <?php
            $servername = "localhost";
            $username = "root";
            $password = ""; 
            $dbname = "qly_thuvien";

            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Kết nối thất bại: " . $conn->connect_error);
            }
            $message = "";
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $username = $_POST['username'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                if ($password !== $confirm_password) {
                    $message = "Mật khẩu và mật khẩu xác nhận không khớp!";
                } else {
                    $raw_password = $confirm_password;
                    $sql_check = "SELECT * FROM users WHERE username = '$username' OR email = '$email' OR phone = '$phone'";
                    $result_check = $conn->query($sql_check);
                    if ($result_check->num_rows > 0) {
                        $message = "Tên đăng nhập, email, tài khoản, hoặc số điện thoại đã tồn tại!";
                    } else {
                        // Thêm role mặc định là 3 khi tạo tài khoản mới
                        $sql_insert = "INSERT INTO users (username, email, phone, password, role)
                                    VALUES ('$username', '$email', '$phone', '$raw_password', 3)";

                        if ($conn->query($sql_insert) === TRUE) {
                            header("Location: login.php");
                            exit();
                        } else {
                            $message = "Lỗi: " . $conn->error;
                        }
                    }
                }
            }
            $conn->close();
            ?>
            <?php if (!empty($message)) { ?>
                <p class="message"><?php echo $message; ?></p>
            <?php } 
        ?>
        <a href="login.php">Đăng nhập</a></p>
    </form>
                
            </div>
        </div>
    </div>
</body>
</html>