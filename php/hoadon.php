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

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['username'];

// Lấy userID của người dùng hiện tại
$sql_user = "SELECT userID FROM users WHERE username = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $currentUser);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();

// Kiểm tra xem user có tồn tại không
if (!$user) {
    die("Lỗi: Không tìm thấy người dùng với username '$currentUser'. Vui lòng đăng nhập lại.");
}

$userID = $user['userID'];

$book_items = isset($_SESSION['book']) ? $_SESSION['book'] : [];
$totalAmount = 0;
$totalDeposit = 0;

if (!empty($book_items)) {
    $ngayTao = date('Y-m-d H:i:s');
    
    // Tạo mã hóa đơn mới dựa trên mã hóa đơn cuối cùng trong CSDL
    $sql_last_id = "SELECT MaHoaDon FROM hoadon ORDER BY MaHoaDon DESC LIMIT 1";
    $result_last_id = $conn->query($sql_last_id);
    
    if ($result_last_id && $row_last_id = $result_last_id->fetch_assoc()) {
        $last_id = $row_last_id['MaHoaDon'];
        $numeric_part = intval(substr($last_id, 2));
        $new_numeric = $numeric_part + 1;
        $maHoaDon = 'HD' . str_pad($new_numeric, 3, '0', STR_PAD_LEFT);
    } else {
        $maHoaDon = 'HD001';
    }

    // Kiểm tra trùng lặp MaHoaDon
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM hoadon WHERE MaHoaDon = ?");
    $stmt_check->bind_param("s", $maHoaDon);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    if ($result->fetch_row()[0] > 0) {
        $new_numeric++;
        $maHoaDon = 'HD' . str_pad($new_numeric, 3, '0', STR_PAD_LEFT);
    }
    $stmt_check->close();

    // Thêm hóa đơn mới với userID
    $stmt_hoadon = $conn->prepare("INSERT INTO hoadon (MaHoaDon, userID, NgayTao, TongTien) VALUES (?, ?, ?, ?)");
    $initialTotal = 0; // Sẽ cập nhật sau khi tính tổng
    $stmt_hoadon->bind_param("sisi", $maHoaDon, $userID, $ngayTao, $initialTotal);
    if (!$stmt_hoadon->execute()) {
        die("Lỗi khi thêm hóa đơn: " . $stmt_hoadon->error);
    }

    // Chuẩn bị statement cho chi tiết hóa đơn
    // Kiểm tra xem bảng chitiethoadon có cột userID hay không
    $result = $conn->query("SHOW COLUMNS FROM chitiethoadon LIKE 'userID'");
    $hasUserIDColumn = $result->num_rows > 0;

    if ($hasUserIDColumn) {
        // Nếu bảng chitiethoadon có cột userID, thêm userID vào câu lệnh INSERT
        $stmt_chitiet = $conn->prepare("INSERT INTO chitiethoadon (MaHoaDon, MaSach, TenSach, SoLuong, GiaDatCoc, GiaThue, GiaBan, TongTien, userID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    } else {
        // Nếu không có cột userID, sử dụng câu lệnh INSERT ban đầu
        $stmt_chitiet = $conn->prepare("INSERT INTO chitiethoadon (MaHoaDon, MaSach, TenSach, SoLuong, GiaDatCoc, GiaThue, GiaBan, TongTien) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    }

    foreach ($book_items as $maSach => $item) {
        // Lấy thông tin sách từ CSDL
        $sql = "SELECT * FROM sach WHERE MaSach = ?";
        $stmt_get_sach = $conn->prepare($sql);
        $stmt_get_sach->bind_param("s", $maSach);
        $stmt_get_sach->execute();
        $result = $stmt_get_sach->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            $tenSach = $row['TenSach'];
            $giaDatCoc = floatval($row['GiaDatCoc']);
            $giaThue = floatval($row['GiaThue']);
            $giaBan = floatval($row['GiaBan']);
            
            // Kiểm tra và lấy số lượng và loại giao dịch từ mảng $item
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $transactionType = isset($item['transaction_type']) ? $item['transaction_type'] : 'buy';
            
            // Đảm bảo giá trị đúng cho giao dịch mua hoặc thuê
            if ($transactionType == 'buy') {
                $tongTien = $giaBan * $quantity;
                $giaDatCoc = 0; // Đặt giá đặt cọc là 0 cho sách mua
                $giaThue = 0; // Không có giá thuê cho sách mua
                $totalAmount += $tongTien;
            } else { // rent
                $tongTien = $giaThue * $quantity;
                $deposit = $giaDatCoc * $quantity;
                $totalAmount += $tongTien;
                $totalDeposit += $deposit;
            }

            // Debug dữ liệu trước khi chèn
            error_log("Chèn chi tiết hóa đơn: MaSach=$maSach, TransactionType=$transactionType, GiaDatCoc=$giaDatCoc, TongTien=$tongTien");

            // Thêm chi tiết hóa đơn
            if ($hasUserIDColumn) {
                $stmt_chitiet->bind_param("sssiidddi", $maHoaDon, $maSach, $tenSach, $quantity, $giaDatCoc, $giaThue, $giaBan, $tongTien, $userID);
            } else {
                $stmt_chitiet->bind_param("sssiiddd", $maHoaDon, $maSach, $tenSach, $quantity, $giaDatCoc, $giaThue, $giaBan, $tongTien);
            }
            if (!$stmt_chitiet->execute()) {
                die("Lỗi khi thêm chi tiết hóa đơn: " . $stmt_chitiet->error);
            }
            
            $stmt_get_sach->close();
        }
    }

    // Cập nhật tổng tiền trong hóa đơn (bao gồm cả tiền đặt cọc)
    $grandTotal = $totalAmount + $totalDeposit;
    $update_sql = "UPDATE hoadon SET TongTien = ? WHERE MaHoaDon = ?";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("ds", $grandTotal, $maHoaDon);
    $stmt_update->execute();
    $stmt_update->close();

    $stmt_chitiet->close();
    $stmt_hoadon->close();

    // Xóa giỏ hàng sau khi đã tạo hóa đơn
    unset($_SESSION['book']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn Thanh Toán</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin-top:150px;
        }

        .container {
            background-color: #fff;
            width: 90%;
            max-width: 800px;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
        }

        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }

        .invoice-info {
            margin-bottom: 20px;
            font-size: 14px;
        }

        .invoice-info p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }

        .total {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            margin: 20px 0;
            padding: 10px 0;
            border-top: 2px solid #eee;
        }

        .actions {
            margin-top: 30px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .empty-message {
            text-align: center;
            margin: 30px 0;
            font-size: 18px;
            color: #555;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .print-btn {
            background-color: #28a745;
            color: white;
        }

        .print-btn:hover {
            background-color: #218838;
        }

        .home-btn {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .home-btn:hover {
            background-color: #0069d9;
        }

        @media print {
            .actions {
                display: none;
            }
            .container {
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<?php include 'header2.php';?>

<div class="main-content">
    <div class="container">
        <div class="header">
            <h1>Hóa Đơn Thanh Toán</h1>
        </div>
        
        <?php if (isset($maHoaDon)): ?>
            <div class="invoice-info">
                <p><strong>Mã hóa đơn:</strong> <?php echo $maHoaDon; ?></p>
                <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($ngayTao)); ?></p>
            </div>
            
            <table>
                <tr>
                    <th>Tên sách</th>
                    <th>Số lượng</th>
                    <th>Loại giao dịch</th>
                    <th>Giá</th>
                    <?php
                    // Kiểm tra xem có sản phẩm nào có loại giao dịch là "rent" hay không
                    $hasRentItems = false;
                    $sql_check_rent = "SELECT COUNT(*) FROM chitiethoadon WHERE MaHoaDon = ? AND GiaDatCoc > 0";
                    $stmt_check_rent = $conn->prepare($sql_check_rent);
                    $stmt_check_rent->bind_param("s", $maHoaDon);
                    $stmt_check_rent->execute();
                    $result_check_rent = $stmt_check_rent->get_result();
                    if ($result_check_rent->fetch_row()[0] > 0) {
                        $hasRentItems = true;
                    }
                    $stmt_check_rent->close();
                    if ($hasRentItems):
                    ?>
                    <th>Tiền Đặt Cọc</th>
                    <?php endif; ?>
                    <th>Tổng tiền</th>
                </tr>
                <?php
                $sql = "SELECT * FROM chitiethoadon WHERE MaHoaDon = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $maHoaDon);
                $stmt->execute();
                $result = $stmt->get_result();

                $grandTotal = 0;
                $totalAmount = 0;
                $totalDeposit = 0;
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $quantity = $row['SoLuong'];
                        $giaThue = floatval($row['GiaThue']);
                        $giaDatCoc = floatval($row['GiaDatCoc']);
                        $giaBan = floatval($row['GiaBan']);
                        $trangThai = $row['TrangThai'];

                        // Giá hiển thị là giá cơ bản (không nhân với số lượng)
                        $price = ($giaDatCoc == 0) ? $giaBan : $giaThue;
                        // Tiền đặt cọc hiển thị là giá cơ bản (không nhân với số lượng)
                        $deposit = ($giaDatCoc > 0) ? $giaDatCoc : 0;
                        // Tổng tiền tính theo số lượng
                        $itemTotal = ($giaDatCoc == 0) ? ($giaBan * $quantity) : (($giaThue * $quantity) + ($giaDatCoc * $quantity));
                        // Cộng dồn tổng tiền và tiền đặt cọc
                        if ($giaDatCoc == 0) {
                            $totalAmount += ($giaBan * $quantity);
                        } else {
                            $totalAmount += ($giaThue * $quantity);
                            $totalDeposit += ($giaDatCoc * $quantity);
                        }
                        $grandTotal += $itemTotal;
                        ?>
                        <tr>
                            <td><?php echo $row['TenSach']; ?></td>
                            <td><?php echo $quantity; ?></td>
                            <td><?php echo ($trangThai == 'Đã mua') ? 'Đã mua' : 'Thuê'; ?></td>
                            <td><?php echo number_format($price, 0, ',', '.'); ?> VND</td>
                            <?php if ($hasRentItems): ?>
                            <td>
                                <?php echo ($giaDatCoc > 0) ? number_format($deposit, 0, ',', '.') . ' VND' : '-'; ?>
                            </td>
                            <?php endif; ?>
                            <td><?php echo number_format($itemTotal, 0, ',', '.'); ?> VND</td>
                        </tr>
                    <?php }
                } else {
                    echo "<tr><td colspan='6'>Không có dữ liệu</td></tr>";
                }
                $stmt->close();
                ?>
            </table>
            
            <p class="total">
                <?php if ($totalDeposit > 0): ?>
                Tổng tiền đặt cọc: <?php echo number_format($totalDeposit, 0, ',', '.'); ?> VND<br>
                <?php endif; ?>
                Tổng tiền: <?php echo number_format($totalAmount, 0, ',', '.'); ?> VND<br>
                <?php if ($totalDeposit > 0): ?>
                Thành tiền: <?php echo number_format($grandTotal, 0, ',', '.'); ?> VND
                <?php endif; ?>
            </p>
            
            <div class="actions">
                <button class="btn print-btn" onclick="window.print()">In Hóa Đơn</button>
                <a href="trangchu.php" class="btn home-btn">Quay về trang chủ</a>
            </div>
        <?php else: ?>
            <p class="empty-message">Hóa đơn trống hoặc giỏ hàng đã được xử lý.</p>
            <div class="actions">
                <a href="trangchu.php" class="btn home-btn">Quay về trang chủ</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'back.php'; ?> 
<?php include 'footer.php'; ?>
</body>
</html>

<?php 
$conn->close();
?>