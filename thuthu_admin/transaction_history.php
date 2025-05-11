<?php
// Start session only if not already active
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

$conn->set_charset("utf8mb4");

// Check if userID is provided
if (!isset($_GET['userID']) || !is_numeric($_GET['userID'])) {
    die("Không tìm thấy người dùng!");
}

$userID = (int)$_GET['userID'];

// Fetch user details
$user_sql = "SELECT username FROM users WHERE userID = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $userID);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows == 0) {
    die("Người dùng không tồn tại!");
}

$user = $user_result->fetch_assoc();
$username = $user['username'];
$user_stmt->close();

// Fetch transaction history (joining hoadon and chitiethoadon)
$sql = "SELECT h.MaHoaDon, h.NgayTao, h.TongTien, c.MaSach, c.TenSach, c.SoLuong, c.GiaDatCoc, c.GiaThue, c.GiaBan, c.TongTien as ChiTietTongTien, c.TrangThai
        FROM hoadon h
        LEFT JOIN chitiethoadon c ON h.MaHoaDon = c.MaHoaDon
        WHERE h.userID = ?
        ORDER BY h.NgayTao DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Giao Dịch - <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #C25964;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--dark);
            color: white;
            padding: 20px 0;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
        }

        .transaction-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-table th, .transaction-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .transaction-table th {
            background-color: var(--dark);
            color: white;
        }

        .transaction-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .transaction-table tr:hover {
            background-color: #f1f1f1;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .status-rented {
            background-color: var(--warning);
        }

        .status-returned {
            background-color: var(--success);
        }

        .status-purchased {
            background-color: var(--primary);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .transaction-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php include 'menu_left_1.php'; ?>
    </div>

    <div class="main-content">
        <header>
            <div class="header-content">
                <div class="logo">
                    <a href="qly_users.php" style="color:white; text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> LỊCH SỬ GIAO DỊCH - <?php echo htmlspecialchars($username); ?>
                    </a>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="transaction-container">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Mã Hóa Đơn</th>
                            <th>Ngày Giao Dịch</th>
                            <th>Mã Sách</th>
                            <th>Tên Sách</th>
                            <th>Số Lượng</th>
                            <th>Giá Thuê</th>
                            <th>Giá Đặt Cọc</th>
                            <th>Giá Bán</th>
                            <th>Tổng Tiền (Chi Tiết)</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['MaHoaDon']); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($transaction['NgayTao'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['MaSach'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['TenSach'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['SoLuong'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($transaction['GiaThue'] ?? 0, 2); ?> VNĐ</td>
                                    <td><?php echo number_format($transaction['GiaDatCoc'] ?? 0, 2); ?> VNĐ</td>
                                    <td><?php echo number_format($transaction['GiaBan'] ?? 0, 2); ?> VNĐ</td>
                                    <td><?php echo number_format($transaction['ChiTietTongTien'] ?? 0, 2); ?> VNĐ</td>
                                    <td>
                                        <?php
                                            $status_class = '';
                                            $status_name = $transaction['TrangThai'] ?? 'N/A';
                                            switch ($status_name) {
                                                case 'Chưa trả':
                                                    $status_class = 'status-rented';
                                                    break;
                                                case 'Đã trả':
                                                    $status_class = 'status-returned';
                                                    break;
                                                case 'Đã mua':
                                                    $status_class = 'status-purchased';
                                                    break;
                                                default:
                                                    $status_name = 'N/A';
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_name; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align: center;">Không có giao dịch nào!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>