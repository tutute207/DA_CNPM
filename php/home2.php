<?php
session_start();
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "qly_thuvien";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if (isset($_GET['add_to_book'])) {
    $MaSach = $_GET['add_to_book'];

    if (!isset($_SESSION['book'])) {
        $_SESSION['book'] = [];
    }

    if (isset($_SESSION['book'][$MaSach])) {
        $_SESSION['book'][$MaSach]++;
    } else {
        $_SESSION['book'][$MaSach] = 1;
    }

    header("Location: home.php");
    exit();
}

$category = isset($_GET['category']) ? $_GET['category'] : '';

if ($category) {
    $sql = "SELECT MaSach, hinhanh, TenSach, GiaThue ,GiaBan ,GiaDatCoc FROM sach WHERE TheLoai = '$category'";
} else {
    $sql = "SELECT MaSach, hinhanh, TenSach, GiaThue,GiaBan,GiaDatCoc FROM sach"; 
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Quản Lý Thư Viện</title>
    <style>
        body {
            background-color: #FBEEC1;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }     
    </style>
</head>
<body>

<?php include 'header2.php'; ?>
<?php include 'banner.php'; ?>  
<?php include 'menu.php'; ?> 
<?php include 'back.php'; ?> 
<?php include 'footer.php'; ?>

</body>
</html>