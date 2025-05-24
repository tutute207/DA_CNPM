<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Footer Layout</title>
    <style>
        .footer {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            background-color: #f1f1f1;
        }

        .footer div {
            flex: 1;
            padding: 0 20px;
        }

        .footer h3 {
            color: #1c1f2e;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .footer p,
        .footer li {
            color: #333;
            margin: 5px 0;
        }

        .footer ul {
            list-style-type: none;
            padding: 0;
        }

        .footer .icon {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        .footer .icon img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .fanpage img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<footer>
    <div class="footer">
        <div>
            <h3>THÔNG TIN WEBSITE</h3>
            <?php
                $address = "Tầng 3, Tòa nhà số HA9 - 218 Lĩnh Nam , Hoàng Mai , Hà Nội";
                $phone = "0977754663";
                $email = "contact@sm4s.vn";
            ?>
            <div class="icon">
            <i class="iconn bi bi-geo-alt-fill"></i>
                <p><?php echo $address; ?></p>
            </div>
            <div class="icon">
            <i class="iconn bi bi-telephone-fill"></i>
            <p><?php echo $phone; ?></p>
            </div>
            <div class="icon">
            <i class="iconn bi bi-envelope-fill"></i>
            <p><?php echo $email; ?></p>
            </div>
        </div>
        <div>
            <h3>SINH VIÊN THỰC HIỆN</h3>
            <ul>
                <li><i class="iconn bi bi-person-fill"></i> Đinh Thế Tú</li>
                
            </ul>
        </div>

        <div>
            <h3>MÃ SỐ SINH VIÊN</h3>
            <ul>
                <li><i class="iconn bi bi-calendar-event"></i>2121051396</li>
               
            </ul>
        </div>
        
    </div>
</footer>

</body>
</html>
