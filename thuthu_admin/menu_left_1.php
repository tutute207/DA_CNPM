<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
$roleName = '';
if ($isLoggedIn) {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : 0;
    if ($role == 1) {
        $roleName = 'Quản Trị Viên';
    } elseif ($role == 2) {
        $roleName = 'Thủ Thư';
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #C25964;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        padding: 20px 0;
    }

    .sidebar-title {
        padding: 15px 20px;
        font-size: 18px;
        font-weight: bold;
        color: #fff;
        background-color: #C25964;
        border-bottom: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .sidebar-title i {
        margin-right: 10px;
        font-size: 24px;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .role-info {
        font-size: 14px;
        color: #ddd;
        margin-top: 5px;
    }

    .menu {
        flex-grow: 1;
        margin-top: 10px;
    }

    .menu ul {
        list-style: none;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        font-size: 16px;
        color: #333;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .menu-item:hover {
        background-color: #f0f0f0;
    }

    .menu-item.active {
        background-color: #007bff;
        color: #fff;
    }

    .menu-item.active:hover {
        background-color: rgb(10, 169, 212);
    }

    .menu-item i {
        margin-right: 10px;
        font-size: 20px;
    }

    .menu-item.bottom {
        margin-top: auto;
    }

    .menu-item a {
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .menu-item a:hover {
        color: inherit;
    }
</style>

<div class="sidebar">
    <div class="sidebar-title">
        <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <i class="iconn bi bi-person-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="role-info"><?php echo htmlspecialchars($roleName); ?></div>
        <?php else: ?>
            <div class="user-info">
                <i class="iconn bi bi-person-circle"></i>
                <span>Guest</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="menu">
        <ul>
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="bi bi-bar-chart"></i> Dashboard
                </a>
            </li>
            
            <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>">
                <a href="admin.php">
                    <i class="bi bi-book"></i> Books
                </a>
            </li>
            <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'qly_users.php' ? 'active' : ''; ?>">
                <a href="qly_users.php">
                    <i class="bi bi-person"></i> Members
                </a>
            </li>
            <li class="menu-item bottom">
                <a href="../php/trangchu.php" onclick="event.preventDefault(); <?php $_SESSION['from_admin'] = true; ?> window.location.href='../php/trangchu.php';">
                    <i class="bi bi-house"></i> Trang chủ
                </a>
            </li>
            <li class="menu-item bottom">
                <a href="../php/login.php">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            menuItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });
</script>