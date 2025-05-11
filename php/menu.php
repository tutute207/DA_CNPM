<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "qly_thuvien";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$categories = ['congnghe', 'vanhoc', 'tho'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản phẩm  - Siêu Sale 12.12</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 0;
    }

    .product-container {
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }

    .product-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        justify-items: center;
        padding: 20px;
    }

    .product-item {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        width: 100%;
        max-width: 240px;
        padding: 8px;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 372px; 
    }

    .product-item:hover {
        transform: scale(1.03);
    }

    .product-item img {
        width: 100%;
        border-radius: 6px;
        margin-bottom: 8px;
        transition: transform 0.2s ease;
        height: 170px; 
        object-fit: cover;
    }

    .product-item:hover img {
        transform: scale(1.03);
    }

    @media (max-width: 768px) {
        .product-list {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }

    .product-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .product-info h3 {
        font-size: 14px;
        color: #333;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .specs {
        font-size: 12px;
        color: #666;
        margin-bottom: 4px;
    }

    .price {
        margin-bottom: 6px;
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
    .rating {
        font-size: 12px;
        color: #f39c12;
        margin-bottom: 4px;
    }

    .add-to-book {
        background-color: #555;
        color: white;
        border: none;
        padding: 6px 15px;
        cursor: pointer;
        border-radius: 4px;
        font-size: 13px;
        transition: background-color 0.3s ease;
    }

    .add-to-book:hover {
        background-color: #E7717D;
    }
</style>
</head>
<body>

<?php
foreach ($categories as $category) {
    echo "<div class='product-container'>";
    if ($category == 'congnghe') {
        echo "<h2>* Sách Công Nghệ</h2>";
    } elseif ($category == 'vanhoc') {
        echo "<h2>** Sách Văn Học</h2>";
    } elseif ($category == 'tho') {
        echo "<h2>*** Thơ</h2>";
    }
    echo "<div class='product-list'>";

    $sql = "SELECT * FROM sach WHERE TheLoai = '$category' LIMIT 5";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='product-item'>";  
            if ($category == 'sách công nghệ') {
                echo "<div class='new-tag'>TECH</div>";
            } elseif ($category == 'sách văn học') {
                echo "<div class='vanhoc-tag'>VĂN HỌC</div>";
            } elseif ($category == 'thơ') {
                echo "<div class='tho-tag'>THƠ</div>";
            }
            echo "<a href='chitietsach.php?id=" . $row['MaSach'] . "'><img src='" . $row['hinhanh'] . "' alt='" . $row['TenSach'] . "'></a>";
            echo "<div class='product-info'>";
            echo "<h3>" . $row['TenSach'] . "</h3>";
            echo "<div class='price'>";
            echo "<p class='gia_datcoc'> Giá đặt cọc:" . number_format($row['GiaDatCoc'], 0, ',', '.') . " đ </p>";
            echo "<p class='gia_thue'> Giá thuê:" . number_format($row['GiaThue'], 0, ',', '.') . " đ </p>";
            echo "<p class='gia_ban'> Giá bán:" . number_format($row['GiaBan'], 0, ',', '.') . " đ </p>";
            echo "</div>";
            echo "<p class='rating'>★★★★★ (x đánh giá)</p>";
            echo "<button class='add-to-book' onclick=\"location.href='sach.php?add_to_book=" . $row['MaSach'] . "'\">Thêm vào tủ sách</button>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p>Không có sách nào trong thể loại này.</p>";
    }

    echo "</div>";
    echo "</div>"; 
}

// Đóng kết nối
$conn->close();
?>
</body>
</html>
