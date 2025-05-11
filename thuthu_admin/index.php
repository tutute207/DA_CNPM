<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
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

    <div style="display: flex;">
        <?php include 'menu_left_1.php'; ?>

        <div style="margin-left: 250px; width: calc(100% - 250px);">
            <?php
            include 'chart.php';
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item a');
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === 'chart.php') {
                        e.preventDefault();
                        window.location.href = '?page=dashboard';
                    }
                });
            });
        });
    </script>
</body>
</html>