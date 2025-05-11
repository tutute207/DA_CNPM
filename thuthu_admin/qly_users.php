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

// Check if user is logged in and get their role
$isLoggedIn = isset($_SESSION['username']);
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : 0;

// Lấy danh sách vai trò từ database
$role_list = [
    '1' => 'Quản trị viên',
    '2' => 'Thủ thư',
    '3' => 'Người dùng'
];

// Xử lý tìm kiếm
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$users = [];
$sql = "SELECT * FROM users";
$whereAdded = false;

if ($search_query) {
    $search_query = $conn->real_escape_string($search_query);
    $sql .= " WHERE (username LIKE '%$search_query%' OR email LIKE '%$search_query%' OR phone LIKE '%$search_query%')";
    $whereAdded = true;
}
if ($currentUserRole == 2) {
    $sql .= $whereAdded ? " AND role = '3'" : " WHERE role = '3'";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Xử lý thêm người dùng (Role 1 và Role 2 đều được phép, nhưng Role 2 chỉ add được Role 3)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    if ($currentUserRole != 1 && $currentUserRole != 2) {
        $error = "Bạn không có quyền thêm người dùng!";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];
        $today = date('Y-m-d');

        // Role 2 can only add users with Role 3
        if ($currentUserRole == 2) {
            $role = '3';
        }

        // Kiểm tra username và email đã tồn tại chưa
        $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, role, CreatedAt) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $password, $email, $phone, $role, $today);

            if ($stmt->execute()) {
                header("Location: qly_users.php?message=" . urlencode("Thêm người dùng thành công!"));
                exit();
            } else {
                $error = "Lỗi khi thêm người dùng: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Xử lý sửa người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $userID = $_POST['userID'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $today = date('Y-m-d');

    // Fetch the user being edited to check their role
    $user_sql = "SELECT role FROM users WHERE userID = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $userID);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $targetUserRole = $user_data['role'];
    $user_stmt->close();

    // Role 1 (Admin) can edit anyone, Role 2 (Thủ thư) can only edit Role 3 (Người dùng)
    if ($currentUserRole == 1 || ($currentUserRole == 2 && $targetUserRole == 3)) {
        // Kiểm tra username và email đã tồn tại chưa (ngoài trừ người dùng hiện tại)
        $check_sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND userID != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $username, $email, $userID);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            // Role 2 cannot change the role, so we keep the original role
            if ($currentUserRole == 2) {
                $role = $targetUserRole;
            }

            // Nếu password không thay đổi
            if (empty($password)) {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, role = ?, UpdatedAt = ? WHERE userID = ?");
                $stmt->bind_param("sssssi", $username, $email, $phone, $role, $today, $userID);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, email = ?, phone = ?, role = ?, UpdatedAt = ? WHERE userID = ?");
                $stmt->bind_param("ssssssi", $username, $password, $email, $phone, $role, $today, $userID);
            }

            if ($stmt->execute()) {
                header("Location: qly_users.php?message=" . urlencode("Cập nhật người dùng thành công!"));
                exit();
            } else {
                $error = "Lỗi khi cập nhật người dùng: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = "Bạn không có quyền chỉnh sửa người dùng này!";
    }
}

// Xử lý xóa người dùng (hard delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $userID = $_POST['userID'];

    // Fetch the user being deleted to check their role
    $user_sql = "SELECT role FROM users WHERE userID = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $userID);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $targetUserRole = $user_data['role'];
    $user_stmt->close();

    // Role 1 (Admin) can delete anyone, Role 2 (Thủ thư) can only delete Role 3 (Người dùng)
    if ($currentUserRole == 1 || ($currentUserRole == 2 && $targetUserRole == 3)) {
        $stmt = $conn->prepare("DELETE FROM users WHERE userID = ?");
        $stmt->bind_param("i", $userID);
        
        if ($stmt->execute()) {
            header("Location: qly_users.php?message=" . urlencode("Xóa người dùng thành công!"));
            exit();
        } else {
            $error = "Lỗi khi xóa người dùng: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Bạn không có quyền xóa người dùng này!";
    }
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng Thư Viện</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
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
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
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
        
        .users-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .user-table th {
            background-color: var(--dark);
            color: white;
        }
        
        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .user-table tr:hover {
            background-color: #f1f1f1;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .role-admin {
            background-color: var(--danger);
        }
        
        .role-librarian {
            background-color: var(--primary);
        }
        
        .role-user {
            background-color: var(--success);
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
            max-width: 600px;
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
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            .user-table {
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
<body>
    <div class="sidebar">
        <?php include 'menu_left_1.php'; ?>
    </div>
    
    <div class="main-content">
        <header>
            <div class="header-content">
                <div class="logo"><a href="" style="color:white; text-decoration:none;">QUẢN LÝ NGƯỜI DÙNG THƯ VIỆN</a></div>
                <?php if ($currentUserRole == 1 || $currentUserRole == 2): ?>
                    <button id="add-user-btn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Thêm Người Dùng Mới
                    </button>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="container">
            <div class="search-container">
                <form action="" method="GET" style="display: flex; width: 100%;">
                    <input type="text" id="search-input" name="search" class="search-input" placeholder="Tìm kiếm theo tên đăng nhập, email, số điện thoại..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-btn" id="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="users-container">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Mật khẩu</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Vai trò</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                            <th>Lịch Sử Giao Dịch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['userID']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['password']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <?php 
                                        $role_class = '';
                                        $role_name = '';
                                        
                                        switch($user['role']) {
                                            case '1':
                                                $role_class = 'role-admin';
                                                $role_name = 'Quản trị viên';
                                                break;
                                            case '2':
                                                $role_class = 'role-librarian';
                                                $role_name = 'Thủ thư';
                                                break;
                                            case '3':
                                                $role_class = 'role-user';
                                                $role_name = 'Người dùng';
                                                break;
                                            default:
                                                $role_name = 'Chưa xác định';
                                        }
                                    ?>
                                    <span class="role-badge <?php echo $role_class; ?>"><?php echo $role_name; ?></span>
                                </td>
                                <td><?php echo $user['CreatedAt'] !== '0000-00-00' ? date('d/m/Y', strtotime($user['CreatedAt'])) : 'N/A'; ?></td>
                                <td class="user-actions">
                                    <?php if ($currentUserRole == 1 || ($currentUserRole == 2 && $user['role'] == 3)): ?>
                                        <button class="btn btn-warning" onclick='editUser(<?php echo json_encode($user); ?>)'>
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="userID" value="<?php echo htmlspecialchars($user['userID']); ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn xóa người dùng này?');">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="transaction_history.php?userID=<?php echo htmlspecialchars($user['userID']); ?>" class="btn btn-info">
                                        <i class="fas fa-history"></i> Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="pagination">
                <!-- Phân trang có thể được thêm sau nếu cần -->
            </div>
        </div>
    </div>
    
    <!-- Modal Thêm/Sửa Người Dùng -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">Thêm Người Dùng Mới</h2>
                <span class="close">×</span>
            </div>
            <form id="user-form" method="POST">
                <input type="hidden" id="userID" name="userID">
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="username">Tên đăng nhập:</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="password">Mật khẩu:</label>
                            <input type="password" id="password" name="password" class="form-control">
                            <small id="password-help" style="color: #777; display: none;">Để trống nếu không muốn thay đổi mật khẩu</small>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="email">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="phone">Số điện thoại:</label>
                            <input type="text" id="phone" name="phone" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="role">Vai trò:</label>
                    <select id="role" name="role" class="form-control" <?php echo $currentUserRole == 2 ? 'disabled' : 'required'; ?>>
                        <?php if ($currentUserRole == 2): ?>
                            <option value="3" selected>Người dùng</option>
                        <?php else: ?>
                            <option value="">-- Chọn vai trò --</option>
                            <?php foreach ($role_list as $role_id => $role_name): ?>
                                <option value="<?php echo $role_id; ?>"><?php echo $role_name; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
                    <button type="submit" name="add_user" id="save-btn" class="btn btn-primary">Thêm Người Dùng</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast thông báo -->
    <div id="toast" class="toast"></div>

    <script>
        const modal = document.getElementById('user-modal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const addUserBtn = document.getElementById('add-user-btn');
        const userForm = document.getElementById('user-form');
        const modalTitle = document.getElementById('modal-title');
        const saveBtn = document.getElementById('save-btn');
        const toast = document.getElementById('toast');
        const passwordHelp = document.getElementById('password-help');

        // Mở modal thêm người dùng
        if (addUserBtn) {
            addUserBtn.onclick = function() {
                modalTitle.textContent = 'Thêm Người Dùng Mới';
                saveBtn.textContent = 'Thêm Người Dùng';
                saveBtn.name = 'add_user';
                passwordHelp.style.display = 'none';
                document.getElementById('password').required = true;
                userForm.reset();
                modal.style.display = 'block';
            }
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
            userForm.reset();
        }

        // Mở modal sửa người dùng
        function editUser(user) {
            modalTitle.textContent = 'Sửa Thông Tin Người Dùng';
            saveBtn.textContent = 'Cập Nhật';
            saveBtn.name = 'edit_user';
            passwordHelp.style.display = 'block';
            document.getElementById('password').required = false;
            
            document.getElementById('userID').value = user.userID;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = '';
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone;
            document.getElementById('role').value = user.role;

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
        <?php if ($message): ?>
            showToast("<?php echo htmlspecialchars($message); ?>");
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            showToast("<?php echo htmlspecialchars($error); ?>", true);
        <?php endif; ?>
    </script>
</body>
</html>

<?php $conn->close(); ?>