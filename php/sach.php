<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    if (!isset($_SESSION['book'][$MaSach])) {
        $_SESSION['book'][$MaSach] = 1;
    } else {
        $_SESSION['book'][$MaSach]['quantity']++;
    }

    $_SESSION['message'] = "Bạn đã thêm vào tủ sách thành công!";
    header("Location: tusach.php");
    exit();
}

$category = isset($_GET['category']) ? $_GET['category'] : '';
$sql = $category ? "SELECT MaSach, hinhanh, TenSach, GiaThue ,GiaBan,GiaDatCoc FROM sach WHERE theloai = '$category'" : "SELECT MaSach, hinhanh, TenSach , GiaThue ,GiaBan,GiaDatCoc FROM sach";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Quản Lý Thư viện</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            margin: 0;
            padding: 0;
        }
        .center-menu {
            padding: 37px 20px 60px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            width: 1350px;
            justify-self: center;
            position: relative;
        }
        .product {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            background-color: #e1d9d9;
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
    .product:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }


    .product-image:hover {
        transform: scale(1.1);
    }

        .product img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            width: 100%;
            transition: transform 0.3s ease-in-out;
        }
        .product p {
            margin: 5px 0;
            font-size: 16px;
            font-weight: bold;
        }
        .gia_thue {
        color: #e74c3c;
        font-size: 16px;
        font-weight: bold;
    }

    .gia_ban {
        color:rgb(91, 231, 60);
        font-size: 16px;
        font-weight: bold;
        
    }
    .gia_datcoc {
        color:rgb(47, 187, 233);
        font-size: 16px;
        font-weight: bold;
        
    }
        .add-to-book {
            background-color: #555555;
            color: white;
            border: none;
            padding: 8px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .add-to-book:hover {
            background-color: #E7717D;
        }
        .message-box {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            font-size: 18px;
            text-align: center;
        }
    </style>
    <script>
        function showMessage() {
            const messageBox = document.getElementById('messageBox');
            messageBox.style.display = 'block';
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 1000); 
        }
    </script>
</head>
<body onload="<?php if (isset($_SESSION['message'])) { echo 'showMessage();'; unset($_SESSION['message']); } ?>">
    <div class="center-menu">
        
        <div id="messageBox" class="message-box"><?= $_SESSION['message'] ?? '' ?></div>

        <?php
        if ($result->num_rows > 0): 
            while ($row = $result->fetch_assoc()): 
                echo "<div class='product'>";
                echo "<a href='chitietsach.php?id=" . $row['MaSach'] . "'><img src='" . $row['hinhanh'] . "' alt='" . $row['TenSach'] . "'></a>";
          
           
                echo "<h3>" . $row['TenSach'] . "</h3>";
                echo "<p class='gia_datcoc'> Giá đặt cọc:" . number_format($row['GiaDatCoc'], 0, ',', '.') . " đ </p>";
                echo "<p class='gia_thue'> Giá thuê:" . number_format($row['GiaThue'], 0, ',', '.') . " đ </p>";
                echo "<p class='gia_ban'> Giá bán:" . number_format($row['GiaBan'], 0, ',', '.') . " đ </p>";
           
            echo "<button class='add-to-book' onclick=\"location.href='sach.php?add_to_book=" . $row['MaSach'] . "'\">Thêm vào tủ sách</button>";
        ?>
            
        </div>
    <?php endwhile; ?>
<?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
