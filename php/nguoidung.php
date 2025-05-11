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

// Thiết lập cấu hình phân trang
$records_per_page = 5; // Số bản ghi mỗi trang
$page_rent = isset($_GET['page_rent']) ? (int)$_GET['page_rent'] : 1; // Trang hiện tại cho sách mượn
$page_buy = isset($_GET['page_buy']) ? (int)$_GET['page_buy'] : 1; // Trang hiện tại cho sách mua
if ($page_rent < 1) $page_rent = 1;
if ($page_buy < 1) $page_buy = 1;

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Chuyển hướng đến trang đăng nhập nếu chưa đăng nhập
    exit();
}

$currentUser = $_SESSION['username'];

// Xử lý trả sách
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $maHoaDon = $_POST['ma_hoa_don'];
    $maSach = $_POST['ma_sach'];
    $soLuong = (int)$_POST['so_luong'];
    $giaDatCoc = (float)$_POST['gia_dat_coc'];

    // Lấy userID của người dùng hiện tại
    $sql_user = "SELECT userID FROM users WHERE username = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $currentUser);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    $user = $user_result->fetch_assoc();
    $userID = $user['userID'];
    $stmt_user->close();

    // Bắt đầu giao dịch
    $conn->begin_transaction();
    try {
        // Kiểm tra quyền sở hữu hóa đơn
        $stmt_check = $conn->prepare("SELECT userID FROM hoadon WHERE MaHoaDon = ?");
        $stmt_check->bind_param("s", $maHoaDon);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $hoadon_data = $result_check->fetch_assoc();
        
        if (!$hoadon_data || $hoadon_data['userID'] != $userID) {
            throw new Exception("Bạn không có quyền thực hiện thao tác này");
        }
        $stmt_check->close();

        // Ghi log trả sách vào bảng returns
        $stmt_return = $conn->prepare("INSERT INTO returns (MaHoaDon, MaSach, SoLuong, GiaDatCoc) VALUES (?, ?, ?, ?)");
        $stmt_return->bind_param("ssid", $maHoaDon, $maSach, $soLuong, $giaDatCoc);
        $stmt_return->execute();
        $stmt_return->close();

        // Cập nhật tổng tiền trong hóa đơn (trừ đi số tiền thuê của sách đã trả)
        $sql_update_total = "SELECT SUM(TongTien) as new_total FROM chitiethoadon WHERE MaHoaDon = ? AND NOT EXISTS (SELECT 1 FROM returns r WHERE r.MaHoaDon = chitiethoadon.MaHoaDon AND r.MaSach = chitiethoadon.MaSach)";
        $stmt_total = $conn->prepare($sql_update_total);
        $stmt_total->bind_param("s", $maHoaDon);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $new_total = $result_total->fetch_assoc()['new_total'] ?? 0;
        $stmt_total->close();

        $stmt_update = $conn->prepare("UPDATE hoadon SET TongTien = ? WHERE MaHoaDon = ? AND userID = ?");
        $stmt_update->bind_param("dsi", $new_total, $maHoaDon, $userID);
        $stmt_update->execute();
        $stmt_update->close();

        // Commit giao dịch
        $conn->commit();

        // Chuyển hướng lại trang để cập nhật danh sách
        header("Location: nguoidung.php");
        exit();
    } catch (Exception $e) {
        // Rollback giao dịch nếu có lỗi
        $conn->rollback();
        die("Lỗi khi trả sách: " . $e->getMessage());
    }
}

// Lấy thông tin người dùng từ bảng users với ánh xạ vai trò
$sql_user = "SELECT *, 
             CASE 
                 WHEN role = 1 THEN 'Quản Trị Viên'
                 WHEN role = 2 THEN 'Thủ Thư'
                 WHEN role = 3 THEN 'Độc Giả'
                 ELSE 'Không xác định'
             END AS VaiTro 
             FROM users WHERE username = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $currentUser);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();

// Tính tổng số tiền cọc được trả lại
$sql_refunded = "SELECT SUM(r.GiaDatCoc) as total_refunded 
                 FROM returns r 
                 JOIN hoadon h ON r.MaHoaDon = h.MaHoaDon 
                 WHERE h.userID = ?";
$stmt_refunded = $conn->prepare($sql_refunded);
$stmt_refunded->bind_param("i", $user['userID']);
$stmt_refunded->execute();
$refunded_result = $stmt_refunded->get_result();
$total_refunded = $refunded_result->fetch_assoc()['total_refunded'] ?? 0;
$stmt_refunded->close();

// Đếm tổng số sách mượn và sách mua để phân trang
// Đếm tổng số sách mượn
$sql_rent_count = "SELECT COUNT(*) as total FROM chitiethoadon c 
                  JOIN hoadon h ON c.MaHoaDon = h.MaHoaDon 
                  WHERE c.GiaDatCoc > 0 AND h.userID = ?";
$stmt_rent_count = $conn->prepare($sql_rent_count);
$stmt_rent_count->bind_param("i", $user['userID']);
$stmt_rent_count->execute();
$result_rent_count = $stmt_rent_count->get_result();
$row_rent_count = $result_rent_count->fetch_assoc();
$total_rented = $row_rent_count['total'];
$stmt_rent_count->close();

// Tính tổng số trang cho sách mượn
$total_pages_rent = ceil($total_rented / $records_per_page);
if ($page_rent > $total_pages_rent && $total_pages_rent > 0) {
    $page_rent = $total_pages_rent;
}
$offset_rent = ($page_rent - 1) * $records_per_page;

// Đếm tổng số sách mua
$sql_buy_count = "SELECT COUNT(*) as total FROM chitiethoadon c 
                 JOIN hoadon h ON c.MaHoaDon = h.MaHoaDon 
                 WHERE c.GiaDatCoc = 0 AND c.TongTien > 0 AND h.userID = ?";
$stmt_buy_count = $conn->prepare($sql_buy_count);
$stmt_buy_count->bind_param("i", $user['userID']);
$stmt_buy_count->execute();
$result_buy_count = $stmt_buy_count->get_result();
$row_buy_count = $result_buy_count->fetch_assoc();
$total_purchased = $row_buy_count['total'];
$stmt_buy_count->close();

// Tính tổng số trang cho sách mua
$total_pages_buy = ceil($total_purchased / $records_per_page);
if ($page_buy > $total_pages_buy && $total_pages_buy > 0) {
    $page_buy = $total_pages_buy;
}
$offset_buy = ($page_buy - 1) * $records_per_page;

// Lấy danh sách sách mượn (rent) và sách mua (buy) từ bảng chitiethoadon với phân trang
$sql_rent = "SELECT c.*, h.NgayTao, 
             CASE WHEN EXISTS (SELECT 1 FROM returns r WHERE r.MaHoaDon = c.MaHoaDon AND r.MaSach = c.MaSach) THEN 'Đã trả' ELSE 'Chưa trả' END as TrangThai
             FROM chitiethoadon c 
             JOIN hoadon h ON c.MaHoaDon = h.MaHoaDon 
             WHERE c.GiaDatCoc > 0 AND h.userID = ?
             LIMIT ? OFFSET ?"; // Thêm phân trang

$sql_buy = "SELECT c.*, h.NgayTao 
            FROM chitiethoadon c 
            JOIN hoadon h ON c.MaHoaDon = h.MaHoaDon 
            WHERE c.GiaDatCoc = 0 AND c.TongTien > 0 AND h.userID = ?
            LIMIT ? OFFSET ?"; // Thêm phân trang

$rented_books = [];
$purchased_books = [];

// Lấy dữ liệu sách mượn
$stmt_rent = $conn->prepare($sql_rent);
$stmt_rent->bind_param("iii", $user['userID'], $records_per_page, $offset_rent);
$stmt_rent->execute();
$result_rent = $stmt_rent->get_result();
if ($result_rent->num_rows > 0) {
    while ($row = $result_rent->fetch_assoc()) {
        $rented_books[] = $row;
    }
}
$stmt_rent->close();

// Lấy dữ liệu sách mua
$stmt_buy = $conn->prepare($sql_buy);
$stmt_buy->bind_param("iii", $user['userID'], $records_per_page, $offset_buy);
$stmt_buy->execute();
$result_buy = $stmt_buy->get_result();
if ($result_buy->num_rows > 0) {
    while ($row = $result_buy->fetch_assoc()) {
        $purchased_books[] = $row;
    }
} else {
    error_log("Không tìm thấy sách mua cho userID: " . $user['userID'] . ". Lỗi: " . $conn->error);
}
$stmt_buy->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Người Dùng</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f8f8;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-top: 60px; /* Giảm margin để không bị cách xa phần header quá */
            width: 100%;
        }

        .container {
            background-color: #fff;
            width: 100%;
            max-width: 1200px; /* Tăng kích thước container */
            padding: 150px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 0 auto; /* Căn giữa container */
        }

        h1, h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info {
            margin-bottom: 30px;
            font-size: 16px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .user-info p {
            margin: 10px 0;
            flex: 0 0 48%; /* Chia thành 2 cột */
        }

        .table-responsive {
            overflow-x: auto; /* Cho phép cuộn ngang khi màn hình nhỏ */
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Đặt chiều rộng tối thiểu cho bảng */
        }

        th, td {
            padding: 12px 10px; /* Giảm padding để tiết kiệm không gian */
            border: 1px solid #ddd;
            text-align: left;
            font-size: 14px; /* Giảm kích thước font */
        }

        th {
            background-color: #f8f8f8;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .empty-message {
            text-align: center;
            margin: 30px 0;
            font-size: 18px;
            color: #555;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        /* CSS cho phân trang */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            list-style-type: none;
            padding: 0;
        }
        
        .pagination li {
            margin: 0 5px;
        }
        
        .pagination li a {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            background-color: #f8f9fa;
            color: #007bff;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .pagination li.active a {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination li a:hover:not(.active) {
            background-color: #e9ecef;
        }
        
        .pagination-info {
            text-align: center;
            margin-bottom: 10px;
            color: #6c757d;
            font-size: 14px;
        }

        .return-btn {
            background-color: #dc3545;
            color: white;
        }

        .return-btn:hover {
            background-color: #c82333;
        }

        .return-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .home-btn {
            background-color: #007bff;
            color: white;
        }

        .home-btn:hover {
            background-color: #0069d9;
        }

        /* Định dạng cột cụ thể để tiết kiệm không gian */
        .col-id {
            width: 10%;
        }
        .col-name {
            width: 20%;
        }
        .col-qty {
            width: 10%;
        }
        .col-price {
            width: 12%;
        }
        .col-date {
            width: 15%;
        }
        .col-status {
            width: 10%;
        }
        .col-action {
            width: 12%;
        }
    </style>
</head>
<body>
<?php include 'header2.php'; ?>

<div class="main-content">
    <div class="container">
        <h1>Thông Tin Người Dùng</h1>

        <!-- Thông tin cá nhân -->
        <div class="user-info">
            <p><strong>Tên người dùng:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><strong>Vai trò:</strong> <?php echo htmlspecialchars($user['VaiTro']); ?></p>
            <p><strong>Ngày tạo tài khoản:</strong> <?php echo htmlspecialchars($user['CreatedAt']); ?></p>
            <p><strong>Số tiền cọc được trả lại:</strong> <?php echo number_format($total_refunded, 0, ',', '.'); ?> VND</p>
        </div>

        <!-- Sách mượn -->
        <h2>Sách Đã Mượn</h2>
        <?php if ($total_rented > 0): ?>
            <div class="pagination-info">
                Hiển thị <?php echo count($rented_books) ?> trên tổng số <?php echo $total_rented ?> sách mượn
            </div>
            <div class="table-responsive">
                <table>
                    <tr>
                        <th class="col-id">Mã HĐ</th>
                        <th class="col-name">Tên Sách</th>
                        <th class="col-qty">SL</th>
                        <th class="col-price">Giá Thuê</th>
                        <th class="col-price">Đặt Cọc</th>
                        <th class="col-price">Tổng Tiền</th>
                        <th class="col-date">Ngày Tạo</th>
                        <th class="col-status">Trạng Thái</th>
                        <th class="col-action">Chức năng</th>
                    </tr>
                    <?php foreach ($rented_books as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['MaHoaDon']); ?></td>
                            <td><?php echo htmlspecialchars($book['TenSach']); ?></td>
                            <td><?php echo htmlspecialchars($book['SoLuong']); ?></td>
                            <td><?php echo number_format($book['GiaThue'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($book['GiaDatCoc'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($book['TongTien'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($book['NgayTao'])); ?></td>
                            <td><?php echo htmlspecialchars($book['TrangThai']); ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="ma_hoa_don" value="<?php echo htmlspecialchars($book['MaHoaDon']); ?>">
                                    <input type="hidden" name="ma_sach" value="<?php echo htmlspecialchars($book['MaSach']); ?>">
                                    <input type="hidden" name="so_luong" value="<?php echo htmlspecialchars($book['SoLuong']); ?>">
                                    <input type="hidden" name="gia_dat_coc" value="<?php echo htmlspecialchars($book['GiaDatCoc']); ?>">
                                    <button type="submit" name="return_book" class="btn return-btn" <?php echo $book['TrangThai'] == 'Đã trả' ? 'disabled' : ''; ?>>
                                        <?php echo $book['TrangThai'] == 'Đã trả' ? 'Đã trả' : 'Trả sách'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <!-- Phân trang cho sách mượn -->
            <?php if ($total_pages_rent > 1): ?>
                <ul class="pagination">
                    <?php if ($page_rent > 1): ?>
                        <li><a href="?page_rent=1&page_buy=<?php echo $page_buy; ?>">&laquo;</a></li>
                        <li><a href="?page_rent=<?php echo $page_rent - 1; ?>&page_buy=<?php echo $page_buy; ?>">&lsaquo;</a></li>
                    <?php endif; ?>
                    
                    <?php
                    // Hiển thị các số trang
                    $start_page = max(1, $page_rent - 2);
                    $end_page = min($total_pages_rent, $page_rent + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="<?php echo ($i == $page_rent) ? 'active' : ''; ?>">
                            <a href="?page_rent=<?php echo $i; ?>&page_buy=<?php echo $page_buy; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page_rent < $total_pages_rent): ?>
                        <li><a href="?page_rent=<?php echo $page_rent + 1; ?>&page_buy=<?php echo $page_buy; ?>">&rsaquo;</a></li>
                        <li><a href="?page_rent=<?php echo $total_pages_rent; ?>&page_buy=<?php echo $page_buy; ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <p class="empty-message">Bạn chưa mượn sách nào.</p>
        <?php endif; ?>

        <!-- Sách mua -->
        <h2>Sách Đã Mua</h2>
        <?php if ($total_purchased > 0): ?>
            <div class="pagination-info">
                Hiển thị <?php echo count($purchased_books) ?> trên tổng số <?php echo $total_purchased ?> sách mua
            </div>
            <div class="table-responsive">
                <table>
                    <tr>
                        <th class="col-id">Mã HĐ</th>
                        <th class="col-name">Tên Sách</th>
                        <th class="col-qty">SL</th>
                        <th class="col-price">Giá Bán</th>
                        <th class="col-price">Tổng Tiền</th>
                        <th class="col-date">Ngày Tạo</th>
                    </tr>
                    <?php foreach ($purchased_books as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['MaHoaDon']); ?></td>
                            <td><?php echo htmlspecialchars($book['TenSach']); ?></td>
                            <td><?php echo htmlspecialchars($book['SoLuong']); ?></td>
                            <td><?php echo number_format($book['GiaBan'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($book['TongTien'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($book['NgayTao'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <!-- Phân trang cho sách mua -->
            <?php if ($total_pages_buy > 1): ?>
                <ul class="pagination">
                    <?php if ($page_buy > 1): ?>
                        <li><a href="?page_rent=<?php echo $page_rent; ?>&page_buy=1">&laquo;</a></li>
                        <li><a href="?page_rent=<?php echo $page_rent; ?>&page_buy=<?php echo $page_buy - 1; ?>">&lsaquo;</a></li>
                    <?php endif; ?>
                    
                    <?php
                    // Hiển thị các số trang
                    $start_page = max(1, $page_buy - 2);
                    $end_page = min($total_pages_buy, $page_buy + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="<?php echo ($i == $page_buy) ? 'active' : ''; ?>">
                            <a href="?page_rent=<?php echo $page_rent; ?>&page_buy=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page_buy < $total_pages_buy): ?>
                        <li><a href="?page_rent=<?php echo $page_rent; ?>&page_buy=<?php echo $page_buy + 1; ?>">&rsaquo;</a></li>
                        <li><a href="?page_rent=<?php echo $page_rent; ?>&page_buy=<?php echo $total_pages_buy; ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <p class="empty-message">Bạn chưa mua sách nào.</p>
        <?php endif; ?>

        <div class="actions">
            <a href="trangchu.php" class="btn home-btn">Quay về Trang Chủ</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>

<?php 
$conn->close();
?>