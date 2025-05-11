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

    // Khởi tạo giỏ hàng từ session
    if (!isset($_SESSION['book']) || !is_array($_SESSION['book'])) {
        $_SESSION['book'] = [];
    }
    $book_items = $_SESSION['book'];

    // Debug - Ghi log giỏ hàng
    error_log("Giỏ hàng hiện tại: " . print_r($book_items, true));

    // Xử lý cập nhật giỏ hàng
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
        $productId = $_POST['product_id'];
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        error_log("Nhận yêu cầu POST: product_id=$productId, action=$action");

        if ($action == 'remove') {
            if (isset($book_items[$productId])) {
                unset($book_items[$productId]);
                error_log("Đã xóa sản phẩm: $productId");
            }
        } else if ($action == 'increase' || $action == 'decrease') {
            if (!isset($book_items[$productId]) || !is_array($book_items[$productId])) {
                // Nếu sản phẩm chưa tồn tại hoặc không phải là mảng, khởi tạo nó
                $book_items[$productId] = [
                    'quantity' => 1,
                    'transaction_type' => 'buy'
                ];
                error_log("Đã khởi tạo sản phẩm mới: $productId");
            }
            
            $newQuantity = isset($book_items[$productId]['quantity']) ? (int)$book_items[$productId]['quantity'] : 1;
            
            if ($action == 'increase') {
                $newQuantity++;
                error_log("Tăng số lượng sản phẩm $productId lên $newQuantity");
            } elseif ($action == 'decrease') {
                $newQuantity--;
                if ($newQuantity <= 0) {
                    unset($book_items[$productId]);
                    error_log("Đã xóa sản phẩm $productId vì số lượng <= 0");
                } else {
                    $book_items[$productId]['quantity'] = $newQuantity;
                    error_log("Giảm số lượng sản phẩm $productId xuống $newQuantity");
                }
            }
            
            if (isset($book_items[$productId])) {
                $book_items[$productId]['quantity'] = $newQuantity;
            }
        } else if ($action == 'change_type') {
            $newType = $_POST['transaction_type'];
            if (!isset($book_items[$productId]) || !is_array($book_items[$productId])) {
                // Nếu sản phẩm chưa tồn tại hoặc không phải là mảng, khởi tạo nó
                $book_items[$productId] = [
                    'quantity' => 1,
                    'transaction_type' => $newType
                ];
                error_log("Đã khởi tạo sản phẩm mới với loại giao dịch: $productId - $newType");
            } else {
                $book_items[$productId]['transaction_type'] = $newType;
                error_log("Đã thay đổi loại giao dịch: $productId thành $newType");
            }
        } else if ($action == 'add') {
            // Thêm sản phẩm mới vào giỏ hàng
            if (!isset($book_items[$productId]) || !is_array($book_items[$productId])) {
                $book_items[$productId] = [
                    'quantity' => 1,
                    'transaction_type' => isset($_POST['transaction_type']) ? $_POST['transaction_type'] : 'buy'
                ];
                error_log("Đã thêm sản phẩm mới vào giỏ hàng: $productId");
            } else {
                // Nếu sản phẩm đã tồn tại, tăng số lượng
                $book_items[$productId]['quantity'] = (isset($book_items[$productId]['quantity']) ? 
                    (int)$book_items[$productId]['quantity'] : 0) + 1;
                error_log("Đã tăng số lượng sản phẩm hiện có: $productId");
            }
        }
        
        $_SESSION['book'] = $book_items;
        error_log("Giỏ hàng sau khi cập nhật: " . print_r($book_items, true));
        
        header("Location: tusach.php");
        exit();
    }

    // Debug - In giỏ hàng
    error_log("Đang hiển thị giỏ hàng. Số item: " . count($book_items));
    
    $products = [];
    if (!empty($book_items) && is_array($book_items)) {
        // Tạo danh sách MaSach để truy vấn
        $ids = [];
        foreach (array_keys($book_items) as $id) {
            if (is_string($id) || is_numeric($id)) {
                $ids[] = "'" . $conn->real_escape_string($id) . "'";
                error_log("Thêm ID vào danh sách truy vấn: $id");
            }
        }
        
        if (!empty($ids)) {
            $idsString = implode(',', $ids);
            // Thêm trường GiaDatCoc vào câu truy vấn SQL
            $sql = "SELECT MaSach, TenSach, hinhanh, GiaThue, GiaBan, GiaDatCoc FROM sach WHERE MaSach IN ($idsString)";
            error_log("SQL Query: $sql");
            
            $result = $conn->query($sql);
            
            if ($result) {
                error_log("Số lượng sản phẩm tìm thấy: " . $result->num_rows);
                
                while ($row = $result->fetch_assoc()) {
                    $productId = $row['MaSach'];
                    error_log("Đang xử lý sản phẩm từ DB: $productId");
                    
                    if (isset($book_items[$productId])) {
                        $product = $row;
                        
                        // Xác định số lượng và loại giao dịch
                        if (is_array($book_items[$productId])) {
                            $product['quantity'] = isset($book_items[$productId]['quantity']) ? 
                                (int)$book_items[$productId]['quantity'] : 1;
                            $product['transaction_type'] = isset($book_items[$productId]['transaction_type']) ? 
                                $book_items[$productId]['transaction_type'] : 'buy';
                        } else {
                            // Nếu không phải mảng, khởi tạo với giá trị mặc định
                            $product['quantity'] = 1;
                            $product['transaction_type'] = 'buy';
                            // Sửa lại giỏ hàng để lần sau đúng định dạng
                            $book_items[$productId] = [
                                'quantity' => 1,
                                'transaction_type' => 'buy'
                            ];
                        }
                        
                        // Tính giá dựa trên loại giao dịch
                        $price = $product['transaction_type'] == 'buy' ? $product['GiaBan'] : $product['GiaThue'];
                        $product['price'] = $price * $product['quantity']; // Giá = Giá đơn vị * Số lượng
                        $deposit = $product['transaction_type'] == 'rent' ? $product['GiaDatCoc'] * $product['quantity'] : 0;
                        $product['total'] = $product['price'] + $deposit; // Tổng cộng = Giá + Giá đặt cọc (nếu có)
                        $products[] = $product;
                        
                        error_log("Đã thêm sản phẩm $productId vào danh sách hiển thị với số lượng " . $product['quantity']);
                    } else {
                        error_log("Sản phẩm $productId không tồn tại trong giỏ hàng");
                    }
                }
            } else {
                error_log("Lỗi truy vấn SQL: " . $conn->error);
            }
        } else {
            error_log("Không có ID sản phẩm nào để truy vấn");
        }
    } else {
        error_log("Giỏ hàng trống hoặc không phải là mảng");
    }
    
    // Cập nhật lại session giỏ hàng để đảm bảo định dạng đúng
    $_SESSION['book'] = $book_items;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tủ Sách</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 1000px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 30px;
            margin: 150px auto 20px auto;
            flex: 1 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        .quantity-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .quantity-buttons button {
            width: 30px;
            height: 30px;
            font-size: 16px;
            font-weight: normal;
            border: 1px solid #ccc;
            background-color: #f2f2f2;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .quantity-buttons span {
            width: 50px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            font-weight: normal;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
        }
        .transaction-type {
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        .transaction-type select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .total-cost {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }
        .checkout-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            text-align: center;
            text-decoration: none;
        }
        .checkout-btn:hover {
            background-color: #218838;
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
<div class="container">
    <h1>Tủ Sách Của Bạn</h1>
    <?php if (!empty($products)): ?>
        <table>
            <tr>
                <th>Hình ảnh</th>
                <th>Tên sách</th>
                <th>Loại giao dịch</th>
                <th>Số lượng</th>
                <th>Giá</th>
                <?php
                // Kiểm tra xem có sản phẩm nào có loại giao dịch là "rent" hay không
                $hasRentItems = false;
                foreach ($products as $row) {
                    if ($row['transaction_type'] == 'rent') {
                        $hasRentItems = true;
                        break;
                    }
                }
                // Nếu có ít nhất một sản phẩm thuê, hiển thị cột Tiền Đặt Cọc
                if ($hasRentItems):
                ?>
                <th>Tiền Đặt Cọc</th>
                <?php endif; ?>
                <th>Tổng cộng</th>
                <th>Chức năng</th>
            </tr>
            <?php
            $grandTotal = 0;
            $totalDeposit = 0;
            foreach ($products as $row):
                $deposit = $row['transaction_type'] == 'rent' ? $row['GiaDatCoc'] * $row['quantity'] : 0;
                $grandTotal += $row['total'];
                $totalDeposit += $deposit;
            ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($row['hinhanh']); ?>" alt="<?= htmlspecialchars($row['TenSach']); ?>" width="60"></td>
                    <td><?= htmlspecialchars($row['TenSach']); ?></td>
                    <td>
                        <form method="post" class="transaction-type">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['MaSach']); ?>">
                            <select name="transaction_type" onchange="this.form.submit()">
                                <option value="buy" <?= $row['transaction_type'] == 'buy' ? 'selected' : ''; ?>>Mua</option>
                                <option value="rent" <?= $row['transaction_type'] == 'rent' ? 'selected' : ''; ?>>Thuê</option>
                            </select>
                            <input type="hidden" name="action" value="change_type">
                        </form>
                    </td>
                    <td>
                        <form method="post" class="quantity-buttons">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['MaSach']); ?>">
                            <button type="submit" name="action" value="decrease">-</button>
                            <span><?= $row['quantity']; ?></span>
                            <button type="submit" name="action" value="increase">+</button>
                        </form>
                    </td>
                    <td><?= number_format($row['price'], 0, ',', '.') . ' đ'; ?></td>
                    <?php if ($hasRentItems): ?>
                    <td>
                        <?php if ($row['transaction_type'] == 'rent'): ?>
                            <?= number_format($deposit, 0, ',', '.') . ' đ'; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td><?= number_format($row['total'], 0, ',', '.') . ' đ'; ?></td>
                    <td> 
                        <form method="post">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['MaSach']); ?>">
                            <button type="submit" name="action" value="remove" style="background-color: #dc3545; color: white; border: none; padding: 6px 23px; border-radius: 4px; cursor: pointer;">
                                Xóa
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="total-cost">
            <?php if ($totalDeposit > 0): ?>
            Tổng tiền đặt cọc: <?= number_format($totalDeposit, 0, ',', '.') . ' đ'; ?><br>
            <?php endif; ?>
            Tổng tiền: <?= number_format($grandTotal - $totalDeposit, 0, ',', '.') . ' đ'; ?><br>
            <?php if ($totalDeposit > 0): ?>
            Thành tiền: <?= number_format($grandTotal, 0, ',', '.') . ' đ'; ?>
            <?php endif; ?>
        </div>
        <a href="trangchu.php" class="checkout-btn">Tiếp tục tìm sách</a>
        <a href="hoadon.php" class="checkout-btn">Thanh Toán</a>
    <?php else: ?>
        <p>Tủ sách của bạn đang trống.</p>
        <a href="trangchu.php" class="checkout-btn">Tiếp tục tìm sách</a>
    <?php endif; ?>
</div>
<?php include 'back.php'; ?> 
<?php include 'footer.php'; ?>
</body>
</html>

<?php
$conn->close();
?>