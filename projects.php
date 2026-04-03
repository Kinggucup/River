<?php
// 1. เรียกใช้ไฟล์ภาษา
require_once 'lang.php'; 

$host = 'sql100.infinityfree.com'; 
$dbname = 'if0_41356232_condo_db'; 
$username = 'if0_41356232'; 
$password = 'Kinggucup0822';     

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    $stmt_all = $conn->query("SELECT * FROM condo_rooms ORDER BY id DESC");
    $rooms_all = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    $stmt_rent = $conn->query("SELECT * FROM condo_rooms WHERE room_type = 'rent' OR room_type IS NULL ORDER BY id DESC");
    $rooms_rent = $stmt_rent->fetchAll(PDO::FETCH_ASSOC);

    $stmt_sale = $conn->query("SELECT * FROM condo_rooms WHERE room_type = 'sale' ORDER BY id DESC");
    $rooms_sale = $stmt_sale->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) { echo "Connection failed: " . $e->getMessage(); exit; }
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['menu_projects'] ?? 'โครงการของเรา' ?> - Grand Riverside Condo</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS ของคุณคงเดิม 100% ไม่เปลี่ยนแปลงครับ */
        :root { --primary-blue: #0A4DA2; --secondary-blue: #003375; --accent-yellow: #FFC107; --accent-yellow-hover: #e0a800; --text-white: #ffffff; --text-dark: #333333; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Prompt', sans-serif; }
        body { background-color: var(--bg-light); overflow-x: hidden; overflow-y: scroll; }
        
        nav { background-color: var(--primary-blue); position: sticky; top: 0; width: 100%; padding: 20px 5%; display: flex; justify-content: space-between; align-items: center; z-index: 100; color: var(--text-white); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .logo { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px; text-decoration: none; color: white;}
        .logo i { color: var(--accent-yellow); }
        
        .nav-links { display: flex; align-items: center; gap: 30px; transition: 0.3s ease-in-out; }
        .nav-menu { display: flex; list-style: none; gap: 30px; }
        .nav-menu li a { text-decoration: none; color: rgba(255, 255, 255, 0.8); font-weight: 400; transition: color 0.3s; }
        .nav-menu li a:hover, .nav-menu li a.active { color: var(--accent-yellow); text-decoration: underline; text-underline-offset: 5px; }
        .btn-contact { background-color: var(--accent-yellow); color: var(--primary-blue); padding: 10px 25px; border-radius: 50px; text-decoration: none; font-weight: 600; border: none; transition: all 0.3s; cursor: pointer; }
        .hamburger { display: none; font-size: 1.8rem; color: var(--accent-yellow); cursor: pointer; }

        .page-header { background: linear-gradient(135deg, var(--primary-blue) 0%, #003d82 100%); color: white; text-align: center; padding: 60px 20px; margin-bottom: 20px; }
        .page-header h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 10px; color: var(--accent-yellow); }
        
        .tab-container { display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; padding: 0 20px; flex-wrap: wrap; }
        .tab-btn { background: white; border: 2px solid var(--primary-blue); color: var(--primary-blue); padding: 12px 35px; border-radius: 50px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; font-family: 'Prompt', sans-serif; }
        .tab-btn:hover { background: #f0f4f8; }
        .tab-btn.active { background: var(--primary-blue); color: white; box-shadow: 0 5px 15px rgba(10, 77, 162, 0.3); }
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        
        .section-padding { padding: 0 5% 80px 5%; text-align: center; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
        .room-card { background: white; border-radius: 15px; overflow: hidden; border: 1px solid #eee; display: flex; flex-direction: column; transition: 0.3s; text-align: left;}
        .room-card:hover { box-shadow: 0 15px 30px rgba(10, 77, 162, 0.1); transform: translateY(-5px); }
        .room-img { width: 100%; height: 250px; object-fit: cover; }
        .room-info { padding: 25px; display: flex; flex-direction: column; flex-grow: 1; }
        .room-info h3 { color: var(--primary-blue); font-size: 1.5rem; margin-bottom: 5px; }
        .room-price { color: var(--accent-yellow-hover); font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; }
        .room-specs { margin-bottom: 20px; font-size: 0.9rem; color: #555; }
        .btn-view-room { margin-top: auto; width: 100%; padding: 12px; background-color: white; color: var(--primary-blue); border: 2px solid var(--primary-blue); border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 1rem; }
        .btn-view-room:hover { background-color: var(--primary-blue); color: white; }
        .no-room-msg { text-align: center; color: #888; font-size: 1.2rem; padding: 50px 0; grid-column: 1 / -1; }

        /* Modal CSS ของเดิม */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;}
        .modal-content { background-color: #fff; border-radius: 15px; width: 100%; max-width: 1000px; display: flex; flex-direction: column; overflow: hidden; position: relative; animation: fadeIn 0.3s; max-height: 95vh; overflow-y: auto; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 30px; color: #333; cursor: pointer; z-index: 10; background: white; width: 40px; height: 40px; border-radius: 50%; text-align: center; line-height: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: 0.3s;}
        .close-btn:hover { color: var(--accent-yellow-hover); transform: scale(1.1); }
        .modal-top { display: flex; width: 100%; }
        .modal-left { width: 55%; background: #f4f7f6; display: flex; flex-direction: column;}
        .main-img-container { position: relative; width: 100%; height: 400px; cursor: zoom-in; }
        .main-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .zoom-hint { position: absolute; bottom: 15px; right: 15px; background: rgba(0,0,0,0.6); color: white; padding: 5px 15px; border-radius: 50px; font-size: 0.85rem; pointer-events: none; }
        .gallery-strip { display: flex; gap: 10px; padding: 15px; overflow-x: auto; background: #fff; border-top: 1px solid #eee; }
        .gallery-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent; opacity: 0.7; transition: 0.3s; }
        .gallery-thumb:hover, .gallery-thumb.active { opacity: 1; border-color: var(--primary-blue); }
        .modal-right { width: 45%; padding: 40px; text-align: left; background: var(--primary-blue); color: white; }
        .modal-right h2 { color: var(--accent-yellow); font-size: 2rem; margin-bottom: 10px; }
        .modal-right .m-price { font-size: 1.3rem; margin-bottom: 25px; font-weight: 300; }
        .modal-right ul { list-style: none; margin-bottom: 25px; }
        .modal-right ul li { margin-bottom: 15px; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .modal-details-box { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 30px; font-size: 0.95rem; line-height: 1.6; color: #e0e0e0; white-space: pre-line; }
        .modal-bottom { width: 100%; background-color: #fff; padding: 30px 40px 40px 40px; text-align: left;}
        .map-container { width: 100%; height: 350px; border-radius: 8px; overflow: hidden; border: 1px solid #eee; background-color: #f9f9f9; display: flex; align-items: center; justify-content: center; }
        .map-container iframe { width: 100%; height: 100%; border: none; }
        .btn-modal-contact { display: block; width: 100%; text-align: center; padding: 15px; background: var(--accent-yellow); color: var(--primary-blue); font-weight: bold; border-radius: 50px; text-decoration: none; transition: 0.3s; }
        
        /* Lightbox CSS */
        .lightbox { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.95); backdrop-filter: blur(5px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
        .lightbox.show { opacity: 1; }
        .lightbox img { max-width: 85%; max-height: 85%; object-fit: contain; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.5); transform: scale(0.9); transition: transform 0.3s; user-select: none; }
        .lightbox.show img { transform: scale(1); }
        .lightbox-close { position: absolute; top: 20px; right: 30px; font-size: 40px; color: white; cursor: pointer; transition: 0.3s; z-index: 10001; }
        .lightbox-close:hover { color: var(--accent-yellow); transform: scale(1.1); }
        .lb-prev, .lb-next { cursor: pointer; position: absolute; top: 50%; transform: translateY(-50%); width: 50px; height: 50px; color: white; font-size: 30px; font-weight: bold; transition: 0.3s; user-select: none; background-color: rgba(255,255,255,0.1); border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 10001; }
        .lb-next { right: 30px; }
        .lb-prev { left: 30px; }
        .lb-prev:hover, .lb-next:hover { background-color: var(--accent-yellow); color: var(--primary-blue); transform: translateY(-50%) scale(1.1); }

        @media (max-width: 768px) { 
            .modal-top { flex-direction: column; } 
            .modal-left, .modal-right { width: 100%; } 
            .main-img-container { height: 250px; } 
            .lb-prev { left: 10px; } 
            .lb-next { right: 10px; } 

            .hamburger { display: block; }
            .nav-links { position: absolute; top: 100%; left: 0; width: 100%; background-color: var(--primary-blue); flex-direction: column; padding: 0; max-height: 0; overflow: hidden; box-shadow: 0 10px 10px rgba(0,0,0,0.1); }
            .nav-links.active { padding: 20px 0; max-height: 400px; }
            .nav-menu { flex-direction: column; gap: 15px; text-align: center; width: 100%; }
            .nav-menu li a { display: block; padding: 10px; }
            .btn-contact { margin-top: 10px; }

            .tab-container { flex-direction: row !important; flex-wrap: nowrap !important; justify-content: flex-start !important; overflow-x: auto !important; padding-bottom: 15px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
            .tab-container::-webkit-scrollbar { display: none; }
            .tab-btn { white-space: nowrap !important; flex-shrink: 0 !important; font-size: 0.95rem; padding: 10px 20px; }
            .page-header { padding: 40px 15px; }
            .page-header h1 { font-size: 1.8rem; }
            .page-header p { font-size: 0.9rem; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="index.php" class="logo"><i class="fa-solid fa-building"></i>   RIVER VIEW</a>
        
        <div class="hamburger" onclick="toggleMobileMenu()">
            <i class="fa-solid fa-bars"></i>
        </div>

        <div class="nav-links" id="navLinks">
            <ul class="nav-menu">
                <li><a href="index.php"><?= $t['menu_home'] ?? 'หน้าแรก' ?></a></li>
                <li><a href="projects.php" class="active"><?= $t['menu_projects'] ?? 'โครงการ' ?></a></li>
                <li><a href="index.php#recommended"><?= $t['menu_recommended'] ?? 'โครงการแนะนำ' ?></a></li>
            </ul>
            <button class="btn-contact"><?= $t['btn_contact'] ?? 'ติดต่อเรา' ?></button>
            
            <div class="lang-switch" style="display: flex; gap: 10px; align-items: center; margin-left: 20px;">
                <a href="?lang=th" style="color: <?= $current_lang == 'th' ? 'var(--accent-yellow)' : '#fff' ?>; text-decoration: none; font-weight: bold;">TH</a>
                <span style="color: rgba(255,255,255,0.5);">|</span>
                <a href="?lang=en" style="color: <?= $current_lang == 'en' ? 'var(--accent-yellow)' : '#fff' ?>; text-decoration: none; font-weight: bold;">EN</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <h1><?= $t['project_title'] ?? 'รูปแบบห้องพักของเรา' ?></h1>
        <p><?= $t['project_subtitle'] ?? 'เลือกพื้นที่ความสุขที่ตอบโจทย์ไลฟ์สไตล์ของคุณ' ?></p>
    </div>

    <div class="tab-container">
        <button class="tab-btn active" onclick="switchTab('all')"><i class="fa-solid fa-layer-group"></i> <?= $t['filter_all'] ?? 'รวมทั้งหมด' ?></button>
        <button class="tab-btn" onclick="switchTab('rent')"><i class="fa-solid fa-key"></i> <?= $t['filter_rent'] ?? 'สำหรับเช่า' ?></button>
        <button class="tab-btn" onclick="switchTab('sale')"><i class="fa-solid fa-hand-holding-dollar"></i> <?= $t['filter_sale'] ?? 'สำหรับขาย' ?></button>
    </div>

    <section class="section-padding">
        
        <div id="tab-all" class="tab-content active">
            <div class="room-grid">
                <?php if(count($rooms_all) > 0): ?>
                    <?php foreach ($rooms_all as $room): ?>
                        <?= renderRoomCard($room) ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-room-msg"><i class="fa-regular fa-folder-open"></i> <?= ($current_lang == 'en') ? 'No rooms available' : 'ยังไม่มีห้องพักในระบบ' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab-rent" class="tab-content">
            <div class="room-grid">
                <?php if(count($rooms_rent) > 0): ?>
                    <?php foreach ($rooms_rent as $room): ?>
                        <?= renderRoomCard($room) ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-room-msg"><i class="fa-regular fa-folder-open"></i> <?= ($current_lang == 'en') ? 'No rooms for rent' : 'ยังไม่มีห้องพักสำหรับเช่าในขณะนี้' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab-sale" class="tab-content">
            <div class="room-grid">
                <?php if(count($rooms_sale) > 0): ?>
                    <?php foreach ($rooms_sale as $room): ?>
                        <?= renderRoomCard($room) ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-room-msg"><i class="fa-regular fa-folder-open"></i> <?= ($current_lang == 'en') ? 'No rooms for sale' : 'ยังไม่มีห้องพักสำหรับขายในขณะนี้' ?></div>
                <?php endif; ?>
            </div>
        </div>

    </section>

   <?php function renderRoomCard($room) { 
        global $t, $current_lang; 
        
        $id = $room['id'];

        $key_name = 'room_name_' . $id;
        $key_price = 'room_price_' . $id;
        $key_size = 'room_size_' . $id;
        $key_func = 'room_func_' . $id;
        $key_details = 'room_details_' . $id; // ✨ เพิ่มรหัสรายละเอียด

        // ดึงคำแปล (ถ้าไม่มีให้ดึงจากฐานข้อมูลเดิม)
        $room_name = $t[$key_name] ?? ($room['room_name'] ?? 'ไม่มีชื่อ');
        $room_price = $t[$key_price] ?? ($room['price_text'] ?? 'ไม่ระบุราคา');
        $room_size = $t[$key_size] ?? ($room['room_size'] ?? '-');
        $room_func = $t[$key_func] ?? ($room['room_func'] ?? '-');
        $room_details = $t[$key_details] ?? ($room['room_details'] ?? ''); // ✨ ดึงรายละเอียด
        
        $size_label = ($current_lang === 'en') ? 'Size:' : 'ขนาดพื้นที่:';
    ?>
        <div class="room-card">
            <div style="position: relative;">
                <img src="<?= htmlspecialchars($room['image_url']) ?>" alt="Room" class="room-img">
                <?php if(($room['room_type'] ?? 'rent') == 'rent'): ?>
                    <span style="position: absolute; top: 15px; left: 15px; background: var(--primary-blue); color: white; padding: 5px 15px; border-radius: 5px; font-size: 0.9rem; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"><i class="fa-solid fa-key"></i> <?= ($current_lang === 'en') ? 'For Rent' : 'ให้เช่า' ?></span>
                <?php else: ?>
                    <span style="position: absolute; top: 15px; left: 15px; background: var(--accent-yellow); color: var(--text-dark); padding: 5px 15px; border-radius: 5px; font-size: 0.9rem; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.2);"><i class="fa-solid fa-tag"></i> <?= ($current_lang === 'en') ? 'For Sale' : 'ขาย' ?></span>
                <?php endif; ?>
            </div>
            
            <div class="room-info">
                <h3><?= htmlspecialchars($room_name) ?></h3>
                <div class="room-price"><?= htmlspecialchars($room_price) ?></div>
                <div class="room-specs">
                    <div><i class="fa-solid fa-vector-square"></i> <?= $size_label ?> <?= htmlspecialchars($room_size) ?></div>
                    <div style="margin-top: 5px;"><i class="fa-solid fa-bed"></i> <?= htmlspecialchars($room_func) ?></div>
                </div>
                
                <button class="btn-view-room" onclick="openRoomModal(this)"
                    data-title="<?= htmlspecialchars($room_name, ENT_QUOTES) ?>"
                    data-price="<?= htmlspecialchars($room_price, ENT_QUOTES) ?>"
                    data-size="<?= htmlspecialchars($room_size, ENT_QUOTES) ?>"
                    data-func="<?= htmlspecialchars($room_func, ENT_QUOTES) ?>"
                    data-img="<?= htmlspecialchars($room['image_url'], ENT_QUOTES) ?>"
                    data-map="<?= htmlspecialchars($room['map_url'] ?? '', ENT_QUOTES) ?>"
                    data-gallery="<?= htmlspecialchars($room['gallery_images'] ?? '[]', ENT_QUOTES) ?>"
                    data-details="<?= htmlspecialchars($room_details, ENT_QUOTES) ?>"
                ><?= $t['btn_view_details'] ?? 'ดูรายละเอียดเพิ่มเติม' ?></button>
            </div>
        </div>
    <?php } ?>

    <div id="roomModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeRoomModal()">×</span>
            <div class="modal-top">
                <div class="modal-left">
                    <div class="main-img-container" onclick="openLightbox()">
                        <img id="modal-img" src="" alt="Room Image">
                        <div class="zoom-hint"><i class="fa-solid fa-magnifying-glass-plus"></i> <?= ($current_lang === 'en') ? 'Click to zoom' : 'คลิกเพื่อดูรูปขยาย' ?></div>
                    </div>
                    <div class="gallery-strip" id="modal-gallery-strip"></div>
                </div>
                <div class="modal-right">
                    <h2 id="modal-title">Room Name</h2>
                    <div class="m-price" id="modal-price">Price</div>
                    <ul>
                        <li><i class="fa-solid fa-vector-square"></i> <strong><?= ($current_lang === 'en') ? 'Size:' : 'พื้นที่ใช้สอย:' ?></strong> <span id="modal-size"></span></li>
                        <li><i class="fa-solid fa-couch"></i> <strong><?= ($current_lang === 'en') ? 'Functions:' : 'ฟังก์ชัน:' ?></strong> <span id="modal-func"></span></li>
                        <li><i class="fa-solid fa-check-circle"></i> <strong><?= ($current_lang === 'en') ? 'Furnishing:' : 'ตกแต่ง:' ?></strong> Fully Fitted</li>
                    </ul>
                    <div id="modal-details" class="modal-details-box" style="display: none;"></div>
                    <a href="tel:080000000" class="btn-modal-contact"><i class="fa-brands fa-line"></i> <?= ($current_lang === 'en') ? 'Contact / Inquire' : 'สอบถามโปรโมชั่น' ?></a>
                </div>
            </div>
            <div class="modal-bottom">
                <h3 style="color:var(--primary-blue); margin-bottom:15px;"><i class="fa-solid fa-map-location-dot"></i> <?= ($current_lang === 'en') ? 'Location / Map' : 'ทำเลที่ตั้ง' ?></h3>
                <div class="map-container" id="modal-map-container"></div>
            </div>
        </div>
    </div>

    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">×</span>
        <button class="lb-prev" onclick="changeLightboxImg(-1, event)">❮</button>
        <img id="lightbox-img" src="" onclick="event.stopPropagation()">
        <button class="lb-next" onclick="changeLightboxImg(1, event)">❯</button>
    </div>

    <script>
        // --- ฟังก์ชันเปิด/ปิด Hamburger Menu ---
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // --- ฟังก์ชันสลับแท็บ รวมทั้งหมด/เช่า/ขาย ---
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // --- โค้ดควบคุม Modal และ Lightbox คงเดิม 100% ---
        let currentGalleryImages = [];
        let currentLightboxIndex = 0;

        function openRoomModal(btn) {
            const title = btn.getAttribute('data-title');
            const price = btn.getAttribute('data-price');
            const size = btn.getAttribute('data-size');
            const func = btn.getAttribute('data-func');
            const mainImg = btn.getAttribute('data-img');
            const mapUrl = btn.getAttribute('data-map');
            const galleryJson = btn.getAttribute('data-gallery');
            const details = btn.getAttribute('data-details');

            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-price').innerText = price;
            document.getElementById('modal-size').innerText = size;
            document.getElementById('modal-func').innerText = func;
            document.getElementById('modal-img').src = mainImg;
            
            const detailsBox = document.getElementById('modal-details');
            if (details && details.trim() !== '') {
                detailsBox.textContent = details;
                detailsBox.style.display = 'block';
            } else {
                detailsBox.style.display = 'none';
            }

            currentGalleryImages = [mainImg];
            const galleryStrip = document.getElementById('modal-gallery-strip');
            let galleryHtml = `<img src="${mainImg}" class="gallery-thumb active" onclick="changeMainImg(this, '${mainImg}')">`; 
            
            try {
                let extraImages = JSON.parse(galleryJson);
                if (extraImages.length > 0) {
                    extraImages.forEach(imgUrl => {
                        galleryHtml += `<img src="${imgUrl}" class="gallery-thumb" onclick="changeMainImg(this, '${imgUrl}')">`;
                        currentGalleryImages.push(imgUrl); 
                    });
                }
            } catch(e) {}
            galleryStrip.innerHTML = galleryHtml;

            const mapContainer = document.getElementById('modal-map-container');
            if (mapUrl && mapUrl.trim() !== '') {
                mapContainer.innerHTML = `<iframe src="${mapUrl}" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
            } else {
                mapContainer.innerHTML = `<p style="padding: 20px;"><i class="fa-solid fa-circle-info"></i> ยังไม่ได้ระบุตำแหน่งแผนที่</p>`;
            }

            document.getElementById('roomModal').style.display = 'flex';
        }

        function changeMainImg(element, src) {
            document.getElementById('modal-img').src = src;
            let thumbs = document.getElementsByClassName('gallery-thumb');
            for(let i=0; i<thumbs.length; i++){ thumbs[i].classList.remove('active'); }
            element.classList.add('active');
        }

        function closeRoomModal() {
            document.getElementById('roomModal').style.display = 'none';
            document.getElementById('modal-map-container').innerHTML = ''; 
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('roomModal')) { closeRoomModal(); }
        }

        function openLightbox() {
            const currentImgSrc = document.getElementById('modal-img').src;
            currentLightboxIndex = currentGalleryImages.findIndex(img => currentImgSrc.includes(img));
            if (currentLightboxIndex === -1) currentLightboxIndex = 0; 
            updateLightboxView();
            const lb = document.getElementById('lightbox');
            lb.style.display = 'flex';
            setTimeout(() => lb.classList.add('show'), 10);
            document.addEventListener('keydown', handleKeyboardNav);
        }

        function changeLightboxImg(direction, event) {
            event.stopPropagation(); 
            currentLightboxIndex += direction;
            if (currentLightboxIndex >= currentGalleryImages.length) {
                currentLightboxIndex = 0;
            } else if (currentLightboxIndex < 0) {
                currentLightboxIndex = currentGalleryImages.length - 1;
            }
            updateLightboxView();
        }

        function updateLightboxView() { document.getElementById('lightbox-img').src = currentGalleryImages[currentLightboxIndex]; }

        function closeLightbox() {
            const lb = document.getElementById('lightbox');
            lb.classList.remove('show');
            setTimeout(() => lb.style.display = 'none', 300);
            document.removeEventListener('keydown', handleKeyboardNav);
        }

        function handleKeyboardNav(e) {
            if (e.key === 'ArrowRight') { changeLightboxImg(1, e); }
            if (e.key === 'ArrowLeft') { changeLightboxImg(-1, e); }
            if (e.key === 'Escape') { closeLightbox(); }
        }
    </script>
</body>
</html>