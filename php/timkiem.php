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

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$gia_ban = isset($_GET['gia_ban']) ? (int)$_GET['gia_ban'] : 0;
$gia_thue = isset($_GET['gia_thue']) ? (int)$_GET['gia_thue'] : 0;

$sql = "SELECT * FROM sach WHERE TenSach LIKE ?";
$params = ["%$keyword%"];

if (!empty($category) && $category != "Tất cả") {
    $sql .= " AND TheLoai=  ?";
    $params[] = $category;
}

if ($gia_ban > 0) {
    $sql .= " AND GiaBan >= ?";
    $params[] = $gia_ban;
}

if ($gia_thue > 0) {
    $sql .= " AND GiaThue <= ?";
    $params[] = $gia_thue;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm</title>
    <style>
    .container {
        display: flex;
        margin-top: 20px;
    }

    .sidebar {
        width: 300px;
        padding: 20px;
        background-color: #f0f0f5;
        border-right: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }

    .sidebar h3 {
        font-size: 20px;
        color: #333;
        margin-bottom: 15px;
        text-align: center;
    }

    .sidebar form {
        display: flex;
        flex-direction: column;
    }

    .sidebar form label {
        font-size: 14px;
        color: #555;
        margin-bottom: 5px;
        margin-top: 10px;
    }

    .sidebar form input[type="text"],
    .sidebar form input[type="number"],
    .sidebar form select {
        padding: 8px;
        font-size: 14px;
        margin-bottom: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        outline: none;
        transition: border-color 0.3s ease;
    }

    .sidebar form input[type="text"]:focus,
    .sidebar form input[type="number"]:focus,
    .sidebar form select:focus {
        border-color: #E7717D;
    }

    .sidebar form button {
        padding: 10px;
        background-color: #555555;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }
    .sidebar form button:hover {
        background-color: #d4606d;
    }
    .products-container {
        display: flex;
        flex-wrap: wrap; 
        gap: 20px;  
        flex: 1;  
    }
    .product {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: calc(25% - 20px);  
        margin-bottom: 20px;
        text-align: center;
        padding: 15px;
        transition: transform 0.3s ease-in-out;
        box-sizing: border-box;  
    }

    .product:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        transition: transform 0.3s ease-in-out;
    }

    .product-image:hover {
        transform: scale(1.1);
    }

    .product-name {
        font-size: 18px;
        font-weight: bold;
        color: #333;
        margin: 10px 0;
    }

    .gia-thue {
        font-size: 16px;
        color: #FF3366; 
        font-weight: bold;
        
        margin-bottom: 15px;
    }
    .gia-ban {
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
        padding: 10px;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.3s ease;
    }

    .add-to-book:hover {
        background-color: #D85C6E;
    }

    .add-to-book:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .product a {
        display: block;
        text-decoration: none;
    }

    </style>
</head>
<body>
    <?php include 'header2.php'; ?>
    <?php include 'banner.php'; ?>

    <div class="container">
    <div class="sidebar">
        <h3>Tìm kiếm nâng cao</h3>
        <form action="" method="get">
            <label for="keyword">Từ khóa</label>
            <input type="text" name="keyword" id="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
            
            <label for="category">Danh mục</label>
            <select name="category" id="category">
            <option value="Tất cả" <?php if ($category == "Tất cả") echo "selected"; ?>>Tất cả</option>
            <option value="vanhoc" <?php if ($category == "vanhoc") echo "selected"; ?>>Sách văn học</option>
            <option value="congnghe" <?php if ($category == "congnghe") echo "selected"; ?>>Sách công nghệ</option>
            <option value="kynang" <?php if ($category == "kynang") echo "selected"; ?>>Sách kỹ năng</option>
            <option value="khoahoc" <?php if ($category == "khoahoc") echo "selected"; ?>>Khoa học</option>
            <option value="vientuong" <?php if ($category == "vientuong") echo "selected"; ?>>Viễn tưởng</option>
            <option value="thieunhi" <?php if ($category == "thieunhi") echo "selected"; ?>>Thiếu nhi</option>
            <option value="kinhdoanh" <?php if ($category == "kinhdoanh") echo "selected"; ?>>Kinh doanh</option>
            <option value="hoiky" <?php if ($category == "hoiky") echo "selected"; ?>>Hồi ký</option>
            <option value="lichsu" <?php if ($category == "lichsu ") echo "selected"; ?>>Lịch sử</option>
            <option value="tho" <?php if ($category == "tho") echo "selected"; ?>>Thơ</option>
            <option value="truyentranh" <?php if ($category == "truyentranh") echo "selected"; ?>>Truyện tranh</option>
            </select>

            <button type="submit">Tìm kiếm</button>
        </form>
    </div>

    <div class="products-container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $productDetailUrl = "chitietsach.php?id=" . $row["MaSach"];           
                echo "<div class='product'>
                        <a href='" . $productDetailUrl . "'>
                            <img src='" . htmlspecialchars($row["hinhanh"]) . "' alt='" . htmlspecialchars($row["TenSach"]) . "' class='product-image'>
                        </a>
                        <p class='product-name'>" . htmlspecialchars($row["TenSach"]) . "</p>
                        <p class='gia-thue'>Giá thuê: " . number_format($row["GiaThue"], 0, ',', '.') . " VND</p>
                        <p class='gia-ban'>Giá bán: " . number_format($row["GiaBan"], 0, ',', '.') . " VND</p>
                        <button class='add-to-book' onclick=\"addTobook(" . $row["MaSach"] . ")\">Thêm vào tủ sách</button>
                      </div>";
            }
        } else {
            echo "<p>Không tìm thấy sách phù hợp.</p>";
        }

        $stmt->close();
        $conn->close();
        ?>
    </div>
</div>


<?php include 'footer.php'; ?>
</body>
</html>
