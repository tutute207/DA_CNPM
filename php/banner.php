<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slider PHP</title>
    <style>
        .carousel-container {
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            max-width: 100%; 
            margin: auto;
        }

        .small-images-container {
            display: flex;
            margin-top: 150px;
            flex-direction: column;
            gap: 15px;
            width: 400px;
        }

        .small-image {
            margin-left: 20px;
            margin-top: 10px;
            width: 360px;
            border-radius: 10px;
            overflow: hidden;
        }

        .small-image img {
            width: 100%;
            height: 190px;
            display: block;
        }

        .carousel {
            margin-top: 200px;
            width: 700px;
            height: 440px;
            overflow: hidden;
            border-radius: 10px 10px 10px 10px;
        }

        .slides {
            display: flex;
            height: 400px;
            border-radius: 10px;
            transition: transform 0.5s ease;
        }

        .slide {
            min-width: 100%;
            height: 400px;
            border-radius: 10px;
            box-sizing: border-box;
        }

        .dots {
            text-align: center;
            margin-top: -25px;
            margin-left: -420px;
        }

        .dot {
            cursor: pointer;
            height: 10px;
            width: 10px;
            margin: 0 5px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .active,
        .dot:hover {
            background-color: #717171;
        }
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            transform: translateY(50%);
            width: 40px;
            height: 40px;
            padding: 0;
            color: white;
            font-weight: bold;
            font-size: 18px;
            border-radius: 50%; 
            background-color: rgba(0, 0, 0, 0.2); 
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s ease;
            user-select: none;
            z-index: 900; 
        }

        .prev {
            left: 185px;
        }

        .next {
            right: 605px; 
        }

        .prev:hover, .next:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }
    </style>
</head>
<body>

<?php
    $sliderImages = [
        "../image/python_basics.png.",
        "../image/kynang.png",
        "../image/doithua.png",
        "../image/truyentranh.png",
        "../image/hoiky.png",
        "../image/vanhoc.png"
    ];

    $smallImages = [
        "../image/ruatho.png",
        "../image/haiku.png"
    ];
?>
<div class="carousel-container">
    <div class="carousel">
        <div class="slides">
            <?php foreach ($sliderImages as $index => $image): ?>
                <div class="slide">
                    <img src="<?php echo $image; ?>" alt="Hình <?php echo $index + 1; ?>" style="width:100%">
                </div>
            <?php endforeach; ?>
        </div>
        <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
        <a class="next" onclick="plusSlides(1)">&#10095;</a>
    </div>

    <div class="small-images-container">
        <?php foreach ($smallImages as $index => $smallImage): ?>
            <div class="small-image">
                <img src="<?php echo $smallImage; ?>" alt="Ảnh nhỏ <?php echo $index + 1; ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="dots">
    <?php for ($i = 1; $i <= count($sliderImages); $i++): ?>
        <span class="dot" onclick="currentSlide(<?php echo $i; ?>)"></span>
    <?php endfor; ?>
</div>

<script>
    let slideIndex = 1;
    showSlides(slideIndex);

    setInterval(() => {
        plusSlides(1);
    }, 3000);

    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

    function currentSlide(n) {
        showSlides(slideIndex = n);
    }

    function showSlides(n) {
        let i;
        let slides = document.getElementsByClassName("slide");
        let dots = document.getElementsByClassName("dot");
        if (n > slides.length) { slideIndex = 1 }
        if (n < 1) { slideIndex = slides.length }
        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";
    }
</script>

</body>
</html>
