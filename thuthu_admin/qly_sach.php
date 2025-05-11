<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
if (!$isLoggedIn) {
    header("Location: ../php/login.php");
    exit();
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

// Lấy danh sách thể loại duy nhất từ database
$theloai_list = [];
$sql_theloai = "SELECT DISTINCT TheLoai FROM sach ORDER BY TheLoai";
$result_theloai = $conn->query($sql_theloai);
if ($result_theloai->num_rows > 0) {
    while ($row = $result_theloai->fetch_assoc()) {
        $theloai_list[] = $row['TheLoai'];
    }
}

// Xử lý tìm kiếm
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$books = [];
$sql = "SELECT * FROM sach";
if ($search_query) {
    $search_query = $conn->real_escape_string($search_query);
    $sql .= " WHERE TenSach LIKE '%$search_query%' OR TenTacGia LIKE '%$search_query%' OR TheLoai LIKE '%$search_query%'";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Xử lý thêm sách
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
    $maSach = $_POST['masach'];
    $tenSach = $_POST['tensach'];
    $theLoai = $_POST['theloai'];
    $tenTacGia = $_POST['tacgia'];
    $giaThue = $_POST['giathue'];
    $giaDatCoc = $_POST['giadatcoc'];
    $giaBan = !empty($_POST['giaban']) ? $_POST['giaban'] : null;
    $moTa = $_POST['mota'];

    $hinhAnh = null;
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $target_dir = "../anh_sach/";
        $file_name = time() . "_" . basename($_FILES["hinhanh"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
            $hinhAnh = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO sach (MaSach, TenSach, TheLoai, TenTacGia, hinhanh, GiaThue, GiaDatCoc, GiaBan, MoTa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssddds", $maSach, $tenSach, $theLoai, $tenTacGia, $hinhAnh, $giaThue, $giaDatCoc, $giaBan, $moTa);

    if ($stmt->execute()) {
        $message = "Thêm sách thành công!";
        // Refresh book list
        $books = [];
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }
    } else {
        $error = "Lỗi khi thêm sách: " . $conn->error;
    }
    $stmt->close();
}

// Xử lý sửa sách
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_book'])) {
    $maSach = $_POST['masach'];
    $tenSach = $_POST['tensach'];
    $theLoai = $_POST['theloai'];
    $tenTacGia = $_POST['tacgia'];
    $giaThue = $_POST['giathue'];
    $giaDatCoc = $_POST['giadatcoc'];
    $giaBan = !empty($_POST['giaban']) ? $_POST['giaban'] : null;
    $moTa = $_POST['mota'];
    $currentImage = $_POST['current_image'];

    $hinhAnh = $currentImage;
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $target_dir = "../anh_sach/";
        $file_name = time() . "_" . basename($_FILES["hinhanh"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
            if ($currentImage && file_exists($currentImage)) {
                unlink($currentImage);
            }
            $hinhAnh = $target_file;
        }
    }

    $stmt = $conn->prepare("UPDATE sach SET TenSach = ?, TheLoai = ?, TenTacGia = ?, hinhanh = ?, GiaThue = ?, GiaDatCoc = ?, GiaBan = ?, MoTa = ? WHERE MaSach = ?");
    $stmt->bind_param("ssssddds", $tenSach, $theLoai, $tenTacGia, $hinhAnh, $giaThue, $giaDatCoc, $giaBan, $moTa, $maSach);

    if ($stmt->execute()) {
        $message = "Cập nhật sách thành công!";
        // Refresh book list
        $books = [];
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }
    } else {
        $error = "Lỗi khi cập nhật sách: " . $conn->error;
    }
    $stmt->close();
}

// Xử lý xóa sách
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_book'])) {
    $maSach = $_POST['masach'];
    $stmt = $conn->prepare("SELECT hinhanh FROM sach WHERE MaSach = ?");
    $stmt->bind_param("s", $maSach);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['hinhanh'] && file_exists($row['hinhanh'])) {
            unlink($row['hinhanh']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM sach WHERE MaSach = ?");
    $stmt->bind_param("s", $maSach);
    if ($stmt->execute()) {
        $message = "Xóa sách thành công!";
        // Refresh book list
        $books = [];
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }
    } else {
        $error = "Lỗi khi xóa sách: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sách Thư Viện</title>
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
        
        .logo {
            font-size: 24px;
            font-weight: bold;
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
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .search-container {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .books-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .book-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .book-image {
            height: 200px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-info {
            padding: 15px;
        }
        
        .book-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .book-author {
            color: #666;
            margin-bottom: 5px;
        }
        
        .book-category {
            background-color: var(--primary);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .gia-thue {
            font-weight: bold;
            color: red;
            margin-bottom: 10px;
        }
        .gia-ban {
            font-weight: bold;
            color:green;
            margin-bottom: 10px;
        }
        .gia-datcoc {
            font-weight: bold;
            color:blue;
            margin-bottom: 10px;
        }
        
        .book-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            overflow: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: modalOpen 0.3s ease-out;
        }
        
        @keyframes modalOpen {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--dark);
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            margin-top: 15px;
        }
        
        .preview-image {
            max-width: 100%;
            height: 150px;
            object-fit: contain;
            display: none;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }
        
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            color: white;
            font-size: 24px;
        }
        
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% {transform: rotate(0deg);}
            100% {transform: rotate(360deg);}
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: var(--success);
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1050;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .toast-error {
            background-color: var(--danger);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .pagination button.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination button:hover:not(.active) {
            background-color: #f5f5f5;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            .books-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'menu_left_1.php'; ?>
    
    <header>
        <div class="header-content">
            <div class="logo"> <a href="" style="color:white; text-decoration:none;">QUẢN LÝ SÁCH THƯ VIỆN</a>   </div>
            <button id="add-book-btn" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm Sách Mới
            </button>
        </div>
    </header>
    
    <div class="container">
        <div class="search-container">
            <form action="" method="GET" style="display: flex; width: 100%;">
                <input type="text" id="search-input" name="search" class="search-input" placeholder="Tìm kiếm theo tên sách, tác giả, thể loại..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn" id="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div id="books-container" class="books-container">
            <?php foreach ($books as $book): ?>
                <div class="book-card" data-id="<?php echo htmlspecialchars($book['MaSach']); ?>">
                    <div class="book-image">
                        <img src="<?php echo htmlspecialchars($book['hinhanh'] ?? '../anh_sach/default.png'); ?>" alt="<?php echo htmlspecialchars($book['TenSach']); ?>">
                    </div>
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($book['TenSach']); ?></div>
                        <div class="book-author"><?php echo htmlspecialchars($book['TenTacGia']); ?></div>
                        <div class="book-category"><?php echo htmlspecialchars($book['TheLoai']); ?></div>
                        <div class="gia-datcoc">Giá Đặt Cọc: <?php echo number_format($book['GiaDatCoc'], 0, ',', '.'); ?> VNĐ</div>
                        <div class="gia-thue">Giá Thuê: <?php echo number_format($book['GiaThue'], 0, ',', '.'); ?> VNĐ</div>
                        <div class="gia-ban">Giá Bán: <?php echo number_format($book['GiaBan'], 0, ',', '.'); ?> VNĐ</div>
                        <div class="book-actions">
                            <button class="btn btn-warning edit-book-btn" onclick='editBook(<?php echo json_encode($book); ?>)'>
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="masach" value="<?php echo htmlspecialchars($book['MaSach']); ?>">
                                <button type="submit" name="delete_book" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn xóa sách này?');">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="pagination" id="pagination">
            <!-- Phân trang có thể được thêm sau nếu cần -->
        </div>
    </div>
    
    <!-- Modal Thêm/Sửa Sách -->
    <div id="book-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">Thêm Sách Mới</h2>
                <span class="close">&times;</span>
            </div>
            <form id="book-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="current_image" name="current_image">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="masach">Mã Sách:</label>
                            <input type="text" id="masach" name="masach" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="tensach">Tên Sách:</label>
                            <input type="text" id="tensach" name="tensach" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="theloai">Thể Loại:</label>
                            <select id="theloai" name="theloai" class="form-control" required style="background-color: white; color: #333;">
                                <option value="">-- Chọn thể loại --</option>
                                <?php foreach ($theloai_list as $theloai): ?>
                                    <option value="<?php echo htmlspecialchars($theloai); ?>"><?php echo htmlspecialchars($theloai); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="tacgia">Tên Tác Giả:</label>
                            <input type="text" id="tacgia" name="tacgia" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="giathue">Giá Thuê:</label>
                            <input type="number" id="giathue" name="giathue" class="form-control" required min="0" step="1000">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="giadatcoc">Giá Đặt Cọc:</label>
                            <input type="number" id="giadatcoc" name="giadatcoc" class="form-control" required min="0" step="1000">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="giaban">Giá Bán:</label>
                            <input type="number" id="giaban" name="giaban" class="form-control" min="0" step="1000">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="hinhanh">Hình Ảnh:</label>
                            <input type="file" id="hinhanh" name="hinhanh" class="form-control" accept="image/*" onchange="previewImage(event)">
                            <img id="preview-image" class="preview-image">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mota">Mô Tả:</label>
                    <textarea id="mota" name="mota" class="form-control" rows="3"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
                    <button type="submit" name="add_book" id="save-btn" class="btn btn-primary">Thêm Sách</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast thông báo -->
    <div id="toast" class="toast"></div>

    <script>
        const modal = document.getElementById('book-modal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const addBookBtn = document.getElementById('add-book-btn');
        const bookForm = document.getElementById('book-form');
        const modalTitle = document.getElementById('modal-title');
        const saveBtn = document.getElementById('save-btn');
        const toast = document.getElementById('toast');

        // Mở modal thêm sách
        addBookBtn.onclick = function() {
            modalTitle.textContent = 'Thêm Sách Mới';
            saveBtn.textContent = 'Thêm Sách';
            saveBtn.name = 'add_book';
            bookForm.reset();
            document.getElementById('preview-image').style.display = 'none';
            document.getElementById('current_image').value = '';
            modal.style.display = 'block';
        }

        // Đóng modal
        closeBtn.onclick = closeModal;
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            bookForm.reset();
            document.getElementById('preview-image').style.display = 'none';
        }

        // Hiển thị ảnh preview
        function previewImage(event) {
            const preview = document.getElementById('preview-image');
            const file = event.target.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            }
        }

        // Mở modal sửa sách
        function editBook(book) {
            modalTitle.textContent = 'Sửa Thông Tin Sách';
            saveBtn.textContent = 'Cập Nhật';
            saveBtn.name = 'edit_book';
            
            document.getElementById('masach').value = book.MaSach;
            document.getElementById('tensach').value = book.TenSach;
            document.getElementById('theloai').value = book.TheLoai;
            document.getElementById('tacgia').value = book.TenTacGia;
            document.getElementById('giathue').value = book.GiaThue;
            document.getElementById('giadatcoc').value = book.GiaDatCoc;
            document.getElementById('giaban').value = book.GiaBan || '';
            document.getElementById('mota').value = book.MoTa || '';
            document.getElementById('current_image').value = book.hinhanh || '';

            const preview = document.getElementById('preview-image');
            if (book.hinhanh) {
                preview.src = book.hinhanh;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }

            modal.style.display = 'block';
        }

        // Hiển thị toast thông báo
        function showToast(message, isError = false) {
            toast.textContent = message;
            toast.className = 'toast';
            if (isError) {
                toast.classList.add('toast-error');
            }
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Hiển thị thông báo nếu có
        <?php if (isset($message)): ?>
            showToast("<?php echo htmlspecialchars($message); ?>");
        <?php endif; ?>
        <?php if (isset($error)): ?>
            showToast("<?php echo htmlspecialchars($error); ?>", true);
        <?php endif; ?>
    </script>
</body>
</html>

<?php $conn->close(); ?>