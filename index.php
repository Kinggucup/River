<?php
// 1. เรียกใช้ไฟล์ภาษาเป็นบรรทัดแรกเสมอ!
require_once 'lang.php'; 

// 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = 'sql100.infinityfree.com'; 
$dbname = 'if0_41356232_condo_db'; 
$username = 'if0_41356232'; 
$password = 'Kinggucup0822';     

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ดึงข้อมูลส่วน Hero 
    $stmt_hero = $conn->query("SELECT * FROM hero_section LIMIT 1");
    $hero = $stmt_hero->fetch(PDO::FETCH_ASSOC);

    // ดึงรูปภาพ Slider
    $all_slides = [];
    if (!empty($hero['bg_image'])) { $all_slides[] = $hero['bg_image']; }
    if (!empty($hero['slider_images'])) {
        $extra_slides = json_decode($hero['slider_images'], true);
        if(is_array($extra_slides)) { $all_slides = array_merge($all_slides, $extra_slides); }
    }

    // ✨ อัปเดต: ดึงห้องที่แอดมินติ๊ก "แนะนำ" มา 10 ห้องล่าสุด (เพื่อทำสไลเดอร์)
    $stmt_rooms = $conn->query("SELECT * FROM condo_rooms WHERE is_recommended = 1 ORDER BY id DESC LIMIT 10");
    $recommended_rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// เตรียมข้อมูลแบนเนอร์ให้ตรงกับภาษาที่เลือก
$hero_subtitle = ($current_lang === 'en' && !empty($hero['subtitle_en'])) ? $hero['subtitle_en'] : ($hero['subtitle'] ?? '');
$hero_title    = ($current_lang === 'en' && !empty($hero['title_en'])) ? $hero['title_en'] : ($hero['title'] ?? '');
$hero_desc     = ($current_lang === 'en' && !empty($hero['description_en'])) ? $hero['description_en'] : ($hero['description'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Riverside Condo</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        :root { --primary-blue: #0A4DA2; --secondary-blue: #003375; --accent-yellow: #FFC107; --accent-yellow-hover: #e0a800; --text-white: #ffffff; --text-dark: #333333; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Prompt', sans-serif; }
        body { background-color: var(--bg-light); overflow-x: hidden; }
        
        /* Navbar & Hamburger */
        nav { position: absolute; top: 0; width: 100%; padding: 20px 5%; display: flex; justify-content: space-between; align-items: center; z-index: 100; color: var(--text-white); }
        .logo { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px; text-decoration: none; color: white; }
        .logo i { color: var(--accent-yellow); }
        .nav-links { display: flex; align-items: center; gap: 30px; transition: 0.3s ease-in-out; }
        .nav-menu { display: flex; list-style: none; gap: 30px; }
        .nav-menu li a { text-decoration: none; color: rgba(255, 255, 255, 0.8); font-weight: 400; transition: color 0.3s; }
        .nav-menu li a:hover, .nav-menu li a.active { color: var(--accent-yellow); text-decoration: underline; text-underline-offset: 5px; }
        .btn-contact { background-color: var(--accent-yellow); color: var(--primary-blue); padding: 10px 25px; border-radius: 50px; text-decoration: none; font-weight: 600; border: none; transition: all 0.3s; cursor: pointer; }
        .btn-contact:hover { background-color: var(--accent-yellow-hover); transform: scale(1.05); }
        .hamburger { display: none; font-size: 1.8rem; color: var(--accent-yellow); cursor: pointer; }

        /* Hero Section */
        .hero { position: relative; height: 100vh; min-height: 600px; max-height: 900px; display: flex; align-items: center; overflow: hidden; background-color: var(--primary-blue); }
        .hero-bg-left { position: absolute; top: 0; left: 0; width: 55%; height: 100%; background: linear-gradient(135deg, var(--primary-blue) 0%, #003d82 100%); z-index: 1; }
        .hero-img-right { position: absolute; top: 0; right: 0; width: 55%; height: 100%; background-size: cover; background-position: center; z-index: 0; clip-path: ellipse(90% 100% at 75% 50%); opacity: 0; transition: opacity 1s ease-in-out; }
        .hero-img-right.active { opacity: 1; }
        .hero::after { content: ''; position: absolute; top: 0; left: 45%; width: 20%; height: 100%; background: radial-gradient(ellipse at left, var(--primary-blue) 0%, transparent 70%); z-index: 2; pointer-events: none; }
        .hero-content { position: relative; z-index: 10; width: 50%; padding-left: 8%; padding-right: 5%; color: var(--text-white); }
        .hero-subtitle { font-size: 1.1rem; color: var(--accent-yellow); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; position: relative; display: inline-block; }
        .hero-subtitle::after { content: ''; display: block; width: 40px; height: 2px; background: var(--accent-yellow); margin-top: 5px; }
        .hero-title { font-size: 3.5rem; line-height: 1.2; font-weight: 700; margin-bottom: 20px; }
        .hero-desc { font-size: 1rem; font-weight: 300; margin-bottom: 30px; opacity: 0.9; line-height: 1.6; max-width: 80%; }
        .hero-btn { display: inline-block; background-color: transparent; color: var(--text-white); border: 2px solid var(--accent-yellow); padding: 12px 30px; border-radius: 50px; font-size: 1rem; text-decoration: none; font-weight: 500; transition: all 0.3s; }
        .hero-btn:hover { background-color: var(--accent-yellow); color: var(--primary-blue); }
        .hero-indicators { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 10; }
        .dot { width: 10px; height: 10px; background-color: rgba(255,255,255,0.5); border-radius: 50%; cursor: pointer; transition: 0.3s; }
        .dot.active { background-color: var(--accent-yellow); width: 30px; border-radius: 10px; }
        
        /* Section Recommended */
        .section-featured { padding: 80px 5%; text-align: center; overflow: hidden; }
        .section-title { font-size: 2.2rem; color: var(--text-dark); margin-bottom: 50px; font-weight: 700; }
        
        /* Card Style */
        /* ลบ max-width: 400px ออก เพื่อให้การ์ดขยายเต็มคอลัมน์ของมัน */
.card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; text-align: left; transition: transform 0.3s; margin: 10px 0; }
        .card-img-top { width: 100%; height: 250px; object-fit: cover; }
        .card-body { background-color: var(--primary-blue); color: white; padding: 25px; position: relative; }
        .card-body::before { content: ''; position: absolute; top: 0; left: 0; width: 5px; height: 100%; background-color: var(--accent-yellow); }
        
        /* ✨ สไตล์เพิ่มเติมสำหรับ Swiper Slider */
        .swiper { width: 100%; padding-bottom: 50px; }
        .swiper-slide { display: flex; justify-content: center; }
        .swiper-pagination-bullet { width: 12px; height: 12px; background: #ccc; opacity: 1; transition: 0.3s; }
        .swiper-pagination-bullet-active { background: var(--primary-blue); width: 30px; border-radius: 10px; }

        @media (max-width: 992px) {
            .hero-title { font-size: 2.5rem; }
            .hero-bg-left { width: 100%; background: linear-gradient(180deg, var(--primary-blue) 60%, transparent 100%); }
            .hero-img-right { width: 100%; height: 50%; top: auto; bottom: 0; clip-path: none; opacity: 0; }
            .hero-content { width: 100%; text-align: center; padding: 0 20px; margin-top: -150px; }
            .hero-subtitle { margin: 0 auto 10px auto; }
            .hero-subtitle::after { margin: 5px auto 0 auto; }
            .hero-desc { margin: 0 auto 30px auto; }
            .hamburger { display: block; }
            .nav-links { position: absolute; top: 100%; left: 0; width: 100%; background-color: rgba(10, 77, 162, 0.95); backdrop-filter: blur(5px); flex-direction: column; padding: 0; max-height: 0; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
            .nav-links.active { padding: 20px 0; max-height: 400px; }
            .nav-menu { flex-direction: column; gap: 15px; text-align: center; width: 100%; }
            .nav-menu li a { display: block; padding: 10px; font-size: 1.1rem; }
            .btn-contact { margin-top: 10px; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="index" class="logo"><i class="fa-solid fa-building"></i> &nbsp; RIVER VIEW</a>
        <div class="hamburger" onclick="toggleMobileMenu()"><i class="fa-solid fa-bars"></i></div>
        <div class="nav-links" id="navLinks">
            <ul class="nav-menu">
                <li><a href="index" class="active"><?= $t['menu_home'] ?? 'หน้าแรก' ?></a></li>
                
                <li><a href="projects"><?= $t['menu_projects'] ?? 'โครงการ' ?></a></li>
                
                <li><a href="index#recommended"><?= $t['menu_recommended'] ?? 'โครงการแนะนำ นะจ๊ะ' ?></a></li>
            </ul>
            
            <button class="btn-contact"><?= $t['btn_contact'] ?? 'ติดต่อเรา' ?></button>
            <div class="lang-switch" style="display: flex; gap: 10px; align-items: center; margin-left: 20px;">
                <a href="?lang=th" style="color: <?= $current_lang == 'th' ? 'var(--accent-yellow)' : '#fff' ?>; text-decoration: none; font-weight: bold;">TH</a>
                <span style="color: rgba(255,255,255,0.5);">|</span>
                <a href="?lang=en" style="color: <?= $current_lang == 'en' ? 'var(--accent-yellow)' : '#fff' ?>; text-decoration: none; font-weight: bold;">EN</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-bg-left"></div>
        <?php foreach($all_slides as $index => $img): ?>
            <div class="hero-img-right <?= $index === 0 ? 'active' : '' ?>" style="background-image: url('<?= htmlspecialchars($img) ?>');"></div>
        <?php endforeach; ?>
        <div class="hero-content">
            <div class="hero-subtitle"><?= htmlspecialchars($hero_subtitle) ?></div>
            <h1 class="hero-title"><?= nl2br(htmlspecialchars($hero_title)) ?></h1>
            <p class="hero-desc"><?= nl2br(htmlspecialchars($hero_desc)) ?></p>
            <a href="projects.php" class="hero-btn"><?= ($current_lang === 'en') ? 'View Projects' : 'ดูรายละเอียดโครงการ' ?></a>
        </div>
        <div class="hero-indicators">
            <?php foreach($all_slides as $index => $img): ?>
                <div class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></div>
            <?php endforeach; ?>
        </div>
    </header>

    <section id="recommended" class="section-featured">
        <h2 class="section-title"><?= ($current_lang === 'en') ? 'Recommended Projects' : 'โครงการแนะนำ' ?></h2>
        
        <div class="swiper recommendedSwiper">
            <div class="swiper-wrapper">
                
                <?php foreach ($recommended_rooms as $room): 
                    // 1. จัดการชื่อห้อง
                    $room_name = ($current_lang === 'en' && !empty($room['room_name_en'])) ? $room['room_name_en'] : ($room['room_name'] ?? 'ไม่มีชื่อ');
                    
                    // 2. จัดการข้อความราคา
                    $room_price = ($current_lang === 'en' && !empty($room['price_text_en'])) ? $room['price_text_en'] : ($room['price_text'] ?? '');

                    // 3. แปลงประเภทห้อง (rent / sale) ให้เป็นภาษาที่ถูกต้อง
                    $type_text = '';
                    if (($room['room_type'] ?? '') === 'rent') {
                        $type_text = ($current_lang === 'en') ? 'For Rent' : 'ให้เช่า';
                    } else {
                        $type_text = ($current_lang === 'en') ? 'For Sale' : 'ขาย';
                    }

                    $img_src = !empty($room['image_url']) ? $room['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
                ?>
                <div class="swiper-slide">
                    <div class="card">
                        <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($room_name) ?>" class="card-img-top">
                        <div class="card-body">
                            <h3 class="card-title" style="font-size: 1.2rem; margin-bottom: 5px;"><?= htmlspecialchars($room_name) ?></h3>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px;">
                                <span style="font-size: 0.85rem; background: var(--accent-yellow); color: var(--primary-blue); padding: 3px 10px; border-radius: 20px; font-weight: 600;">
                                    <?= $type_text ?>
                                </span>
                                <span style="color: var(--accent-yellow); font-weight: 600;">
                                    <?= htmlspecialchars($room_price) ?>
                                </span>
                            </div>

                            <a href="projects.php" style="display: block; text-align: center; background: rgba(255,255,255,0.1); color: white; padding: 8px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; transition: 0.3s;" onmouseover="this.style.background='var(--accent-yellow)'; this.style.color='var(--primary-blue)';" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white';">
                                <?= ($current_lang === 'en') ? 'View Details' : 'ดูรายละเอียด' ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
            
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
        // ฟังก์ชัน Hamburger Menu
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }

        // ระบบเปลี่ยนภาพแบนเนอร์ด้านบน
        let currentSlide = 0;
        const slides = document.querySelectorAll('.hero-img-right');
        const dots = document.querySelectorAll('.hero-indicators .dot');
        let slideInterval;

        function showSlide(index) {
            if(slides.length === 0) return;
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));
            currentSlide = (index + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }
        function nextSlide() { showSlide(currentSlide + 1); }
        function goToSlide(index) { showSlide(index); clearInterval(slideInterval); startSlider(); }
        function startSlider() { if(slides.length > 1) { slideInterval = setInterval(nextSlide, 5000); } }
        startSlider();

        // ✨ ระบบเปิดใช้งาน Swiper Slider โครงการแนะนำ
        var swiper = new Swiper(".recommendedSwiper", {
            slidesPerView: 1, 
            spaceBetween: 15, // ลดระยะห่างบนมือถือ
            loop: true, 
            autoplay: {
                delay: 3000, 
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                // ถ้าจอใหญ่กว่า 768px (Tablet) โชว์ 2 การ์ด
                768: {
                    slidesPerView: 2,
                    spaceBetween: 20, // ลดระยะห่างบนแท็บเล็ต
                },
                // ถ้าจอใหญ่กว่า 1024px (PC) โชว์ 3 การ์ด
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 25, // ลดระยะห่างบน PC ให้พอดีสายตา
                },
            },
        });
    </script>
</body>
</html>
