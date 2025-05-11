<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Left with Overlay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .wrapper {
            height: 100%;
            
            position: fixed;
            top: 0;
            left: 0;
            z-index: 4;
        }

        .wrapper .menu-btn {
            position: fixed;
            color: white;
            height: 45px;
            width: 45px;
            z-index: 3;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        #btn:checked ~ .menu-btn {
           left: 247px;
        }

        .wrapper .menu-btn i {
            position: absolute;
            font-size: 25px;
            transition: all 0.3s ease;
        }

        .wrapper .menu-btn i.fa-times {
            opacity: 0;
        }

        #btn:checked ~ .menu-btn i.fa-times {
            opacity: 1;
            transform: rotate(-180deg);
        }

        #btn:checked ~ .menu-btn i.fa-bars {
            opacity: 0;
            transform: rotate(180deg);
        }

        #sidebar {
            background-color: #332f2c;
            height: 100vh;
            width: 270px;
            overflow: hidden;
            position: fixed;
            top: 0;
            left: -270px;
            transition: all 0.3s ease;
            z-index: 2;
        }

        #btn:checked ~ #sidebar {
            left: 0;
        }

        #sidebar .title {
            line-height: 133px;
            text-align: center;
            background: #E7717D;
            font-size: 25px;
            font-weight: 600;
            color: #e6f2ff;
            border-bottom: 1px solid #002244;
            position: relative;
            z-index: 1;
        }

        #sidebar .list-items {
            position: relative;
            background: #332f2c;
            width: 100%;
            list-style: none;
        }

        #sidebar .list-items li {
            line-height: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid #1c1c1c;
            transition: all 0.3s ease;
        }

        #sidebar .list-items li:hover {
            border-top: 1px solid transparent;
            border-bottom: 1px solid transparent;
            box-shadow: 0 0px 10px 3px #002244;
        }

        #sidebar .list-items li:first-child {
            border-top: none;
        }

        #sidebar .list-items li a {
            color: #e6f2ff;
            text-decoration: none;
            font-size: 18px;
            font-weight: 500;
            height: 100%;
            width: 100%;
            display: block;
        }

        #sidebar .list-items li a i {
            margin-right: 20px;
        }

        #sidebar .list-items .icons {
            width: 100%;
            height: auto;
            text-align: center;
            margin-top: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        #sidebar .list-items .icons a {
            width: 50px;
            height: 50px;
            font-size: 24px;
            color: #ffffff;
            background: #282626;
            border-radius: 50%;
            border: 2px solid #282626;
            display: flex;
            align-items: center;
            text-decoration: none;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #sidebar .list-items .icons a:hover {
            background-color: #E7717D;
            border-color: #E7717D;
            transform: scale(1.1);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0.8, 0.8);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        #btn:checked ~ .overlay {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <input type="checkbox" id="btn" hidden>
        <label for="btn" class="menu-btn">
            <i class="fas fa-bars"></i>
            <i class="fas fa-times"></i>
        </label>
        <div class="overlay"></div>
        <nav id="sidebar">
            <div class="title">Danh Mục</div>
            <ul class="list-items">
                <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                <li><a href="sach1.php"><i class="fas fa-info-circle"></i>Tất cả sách</a></li>
                <li><a href="lienhe.php"><i class="fas fa-briefcase"></i>Liên hệ</a></li>
                <li><a href="login.php"><i class="fas fa-cogs"></i>Đăng nhập</a></li>
                <li><a href="dangky.php"><i class="fas fa-envelope"></i>Đăng ký</a></li>
         
            <div class="icons">
                <a href="https://www.facebook.com/bianka207"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/giai_minh/"><i class="fab fa-instagram"></i></a>
                <a href="https://mail.google.com/mail/u/0/#inbox"><i class="fas fa-envelope"></i></a>
            </div>
          
        </ul>
        </nav>
    </div>
</body>
</html>
