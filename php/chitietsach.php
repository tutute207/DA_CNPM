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

    $MaSach = isset($_GET['id']) ? $_GET['id'] : '';
    $product = null;

    if ($MaSach) {
        $stmt = $conn->prepare("SELECT MaSach, TenSach, TheLoai, TenTacGia, hinhanh, GiaThue, GiaBan, GiaDatCoc, MoTa FROM sach WHERE MaSach = ?");
        $stmt->bind_param("s", $MaSach);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
        }
        $stmt->close();
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $quantity = max($quantity, 1); // Đảm bảo số lượng tối thiểu là 1

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'increase') {
                $quantity++;
            } elseif ($_POST['action'] === 'decrease') {
                $quantity = max($quantity - 1, 1);
            }
        }

        if (isset($_POST['add_to_book'])) {
            if (!isset($_SESSION['book'])) {
                $_SESSION['book'] = [];
            }

            if (!isset($_SESSION['book'][$MaSach])) {
                $_SESSION['book'][$MaSach] = ['quantity' => $quantity];
            } else {
                $_SESSION['book'][$MaSach]['quantity'] += $quantity;
            }

            header("Location: tusach.php");
            exit();
        }
    }
    $conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Sách - <?php echo htmlspecialchars($product['TenSach'] ?? 'Không tìm thấy'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f8f8;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            width: 80%;
            max-width: 800px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 120px auto 20px auto;
            flex: 1 0 auto;
        }

        .product-gallery-and-details {
            display: flex;
            gap: 15px;
            width: 100%;
        }

        .product-gallery {
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .product-gallery .main-image {
            width: 100%;
            max-width: 300px;
            border-radius: 8px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .product-gallery .main-image:hover {
            transform: scale(1.05);
        }

        .thumbnail-row {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .thumbnail-row img {
            width: 40px;
            height: 40px;
            cursor: pointer;
            border-radius: 6px;
            object-fit: cover;
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
        }

        .thumbnail-row img:hover {
            border-color: #3498db;
        }

        .product-details {
            width: 50%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .product-details h1 {
            font-size: 24px;
            color: #333;
        }

        .product-details .reviews {
            color: #f39c12;
            font-size: 12px;
        }

        .product-details .price {
            font-size: 16px;
            font-weight: bold;
        }

        .rent-price {
            color:red;
        }

        .sell-price {
            color:rgb(45, 225, 45);
        }
        .gia_datcoc {
        color:rgb(47, 187, 233);
        }

        .product-details .short-description {
            line-height: 1.5;
            color: #666;
            font-size: 14px;
            max-width: 400px;
        }

        .product-details .actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-details .actions input {
            width: 40px;
            text-align: center;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .product-details .actions button {
            width: 25px;
            height: 25px;
            background-color: white;
            color: black;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .product-details .actions button:hover {
            background-color: #2c81ba;
        }

        .product-details .actions button[name="add_to_book"] {
            height: 35px;
            width: auto;
            padding: 6px 12px;
            background-color: #555555;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }

        .product-details .actions button[name="add_to_book"]:hover {
            background-color: #E7717D;
        }

        .availability {
            margin-top: 15px;
            font-size: 12px;
        }

        .availability span {
            font-weight: bold;
            color: #3498db;
        }

        .social-icons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .social-icons a {
            text-decoration: none;
            color: #666;
            font-size: 14px;
        }

        .social-icons a:hover {
            color: #3498db;
        }

        .reviews-section {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }

        .reviews-section h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }

        .review-item {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-item h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .review-stars {
            color: #f39c12;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .review-item p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .review-item span {
            font-size: 12px;
            color: #777;
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            width: 100%;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<?php include 'header2.php'; ?>
<?php if ($product): ?>
<div class="container">
    <div class="product-gallery-and-details" style="margin-top:120px">
        <div class="product-gallery">
            <img class="main-image" src="<?php echo htmlspecialchars($product['hinhanh']); ?>" alt="<?php echo htmlspecialchars($product['TenSach']); ?>">
        </div>
        <div class="product-details">
            <h1><?php echo htmlspecialchars($product['TenSach']); ?></h1>
            <p><strong>Tác giả:</strong> <?php echo htmlspecialchars($product['TenTacGia']); ?></p>
            <p><strong>Thể loại:</strong> <?php echo htmlspecialchars($product['TheLoai']); ?></p>
            <div class="price">
                <p class="gia_datcoc">Giá đặt cọc: <?php echo number_format($product['GiaDatCoc'], 0, ',', '.'); ?> đ</p>
                <p class="rent-price">Giá thuê: <?php echo number_format($product['GiaThue'], 0, ',', '.'); ?> đ</p>
                <p class="sell-price">Giá bán: <?php echo is_null($product['GiaBan']) ? 'N/A' : number_format($product['GiaBan'], 0, ',', '.') . ' đ'; ?></p>
            </div>
            <div class="reviews">★★★★★ (999 đánh giá)</div>
            <div class="availability">
                <p>Trạng thái: <span>Còn sách</span></p>
                <p>Vận chuyển: <span>Miễn phí</span></p>
            </div>
            <div class="actions">
                <form method="POST">
                    <button type="submit" name="add_to_book">Thêm vào tủ sách</button>
                </form>
            </div>
        </div>
    </div>
    <div class="product-details">
        <h2>Thông tin chi tiết</h2>
        <p class="short-description">
            <?php echo nl2br(htmlspecialchars($product['MoTa'])); ?>
        </p>       
    </div>
    <div class="social-icons">
        <a href="https://web.facebook.com/">Facebook</a>
        <a href="https://www.youtube.com/">YouTube</a>
        <a href="https://www.instagram.com/">Instagram</a>
    </div>
</div>
<?php else: ?>
<div class="container">
    <p>Không tìm thấy sách.</p>
</div>
<?php endif; ?>
<?php include 'back.php'; ?> 
<?php include 'footer.php'; ?>
</body>
</html>