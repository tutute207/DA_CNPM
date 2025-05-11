<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nút Trở Về Đầu Trang và Liên Hệ Đa Kênh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .contact-button {
            position: fixed;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 24px;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            z-index: 1000;
            text-decoration: none; 
        }

        .contact-button:hover {
            transform: translateY(-5px) scale(1.1); 
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
            opacity: 1; 
        }

        #backToTopBtn {
            bottom: 20px;
            right: 20px;
            background-color: #B0B0B0;
            opacity: 0.5; 
        }
        #zaloBtn {
            bottom: 90px;
            left: 20px;
            background-color: #0084ff;
            opacity: 0.7; 
        }

        #zaloBtn img {
            width: 60%; 
            height: 60%;
            object-fit: cover;
            border-radius: 50%; 
        }

        #facebookBtn {
            bottom: 20px;
            left: 20px;
            background-color: #3b5998;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <button onclick="topFunction()" id="backToTopBtn" class="contact-button">
        <i class="fas fa-arrow-up"></i>
    </button>

    <a href="https://zalo.me/0393298532" target="_blank" id="zaloBtn" class="contact-button">
        <img src="../image/zalo.jpg" alt="Zalo">
    </a>

    <a href="https://www.facebook.com/bianka207" target="_blank" id="facebookBtn" class="contact-button">
        <i class="fab fa-facebook-f"></i>
    </a>

    <script>
        const backToTopBtn = document.getElementById("backToTopBtn");

        window.onscroll = function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTopBtn.style.display = "flex";
            } else {
                backToTopBtn.style.display = "none";
            }
        };

        function topFunction() {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        }
    </script>

</body>
</html>
