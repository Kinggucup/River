<?php
$host = 'sql100.infinityfree.com'; // 1. เช็คใน Hosting ว่าค่า MySQL Hostname คืออะไร (มักไม่ใช่ localhost)
$dbname = 'if0_41356232_condo_db';  // 2. ใส่ชื่อฐานข้อมูลที่สร้างไว้บนโฮสต์ (ต้องมี Prefix)
$username = 'if0_41356232'; 
$password = 'Kinggucup0822';     

try {
    // แก้ไขบรรทัดเชื่อมต่อเล็กน้อยเพื่อให้รองรับค่า Host ใหม่
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // ดึงข้อมูล ห้องพัก (Rooms) ทั้งหมดมาแสดง
    $stmt_rooms = $conn->query("SELECT * FROM condo_rooms ORDER BY id DESC");
    $rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โครงการของเรา - Grand Riverside Condo</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { 
            --primary-blue: #0A4DA2; 
            --secondary-blue: #003375; 
            --accent-yellow: #FFC107; 
            --accent-yellow-hover: #e0a800; 
            --text-white: #ffffff; 
            --text-dark: #333333; 
            --bg-light: #F9FAFB; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Prompt', sans-serif; }
        body { background-color: var(--bg-light); overflow-x: hidden; }
        
        /* --- Navbar สำหรับหน้าย่อย (พื้นหลังทึบ) --- */
        nav { 
            background-color: var(--primary-blue); 
            position: sticky; 
            top: 0; 
            width: 100%; 
            padding: 20px 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            z-index: 100; color: var(--text-white); 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .logo { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px; text-decoration: none; color: white;}
        .logo i { color: var(--accent-yellow); }
        .nav-menu { display: flex; list-style: none; gap: 30px; }
        .nav-menu li a { text-decoration: none; color: rgba(255, 255, 255, 0.8); font-weight: 400; transition: color 0.3s; }
        .nav-menu li a:hover, .nav-menu li a.active { color: var(--accent-yellow); text-decoration: underline; text-underline-offset: 5px; }
        .btn-contact { background-color: var(--accent-yellow); color: var(--primary-blue); padding: 10px 25px; border-radius: 50px; text-decoration: none; font-weight: 600; border: none; transition: all 0.3s; cursor: pointer; }
        .btn-contact:hover { background-color: var(--accent-yellow-hover); transform: scale(1.05); }

        /* --- Page Header --- */
        .page-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #003d82 100%);
            color: white;
            text-align: center;
            padding: 60px 20px;
            margin-bottom: 40px;
        }
        .page-header h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 10px; color: var(--accent-yellow); }
        .page-header p { font-size: 1.1rem; opacity: 0.9; font-weight: 300; }

        /* --- ห้องพัก (Rooms) CSS --- */
        .section-padding { padding: 40px 5% 80px 5%; text-align: center; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
        .room-card { background: white; border-radius: 15px; overflow: hidden; border: 1px solid #eee; display: flex; flex-direction: column; transition: 0.3s; text-align: left;}
        .room-card:hover { box-shadow: 0 15px 30px rgba(10, 77, 162, 0.1); transform: translateY(-5px); }
        .room-img { width: 100%; height: 250px; object-fit: cover; }
        .room-info { padding: 25px; display: flex; flex-direction: column; flex-grow: 1; }
        .room-info h3 { color: var(--primary-blue); font-size: 1.5rem; margin-bottom: 5px; }
        .room-price { color: var(--accent-yellow-hover); font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; }
        .room-specs { margin-bottom: 20px; font-size: 0.9rem; color: #555; }
        .room-specs i { color: var(--primary-blue); width: 20px; text-align: center; }
        .btn-view-room { margin-top: auto; width: 100%; padding: 12px; background-color: white; color: var(--primary-blue); border: 2px solid var(--primary-blue); border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 1rem; }
        .btn-view-room:hover { background-color: var(--primary-blue); color: white; }

        /* --- Modal CSS (ปรับปรุงให้รองรับ Google Maps) --- */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); align-items: center; justify-content: center; overflow-y: auto; padding: 20px; box-sizing: border-box;}
        .modal-content { 
            background-color: #fff; 
            border-radius: 15px; 
            width: 100%; 
            max-width: 1000px; 
            display: flex; 
            flex-direction: column; /* เปลี่ยนเป็นเรียงบนลงล่าง เพื่อให้มีที่ใส่แผนที่ */
            overflow: hidden; 
            position: relative; 
            animation: fadeIn 0.3s; 
            margin: auto;
        }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 30px; color: #333; cursor: pointer; z-index: 10; background: white; width: 40px; height: 40px; border-radius: 50%; text-align: center; line-height: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .close-btn:hover { color: var(--accent-yellow-hover); }
        
        /* ส่วนบนของ Modal (รูป + ข้อมูล) */
        .modal-top {
            display: flex;
            width: 100%;
        }
        .modal-left { width: 55%; }
        .modal-left img { width: 100%; height: 100%; object-fit: cover; }
        .modal-right { width: 45%; padding: 40px; text-align: left; background: var(--primary-blue); color: white; }
        .modal-right h2 { color: var(--accent-yellow); font-size: 2rem; margin-bottom: 10px; }
        .modal-right .m-price { font-size: 1.3rem; margin-bottom: 25px; font-weight: 300; }
        .modal-right ul { list-style: none; margin-bottom: 30px; }
        .modal-right ul li { margin-bottom: 15px; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .modal-right ul li i { color: var(--accent-yellow); margin-right: 10px; width: 20px; }
        
        .btn-modal-contact { display: block; width: 100%; text-align: center; padding: 15px; background: var(--accent-yellow); color: var(--primary-blue); font-weight: bold; border-radius: 50px; text-decoration: none; transition: 0.3s; }
        .btn-modal-contact:hover { background: var(--accent-yellow-hover); transform: scale(1.05); }

        /* ส่วนล่างของ Modal (แผนที่ Google Maps) */
        .modal-bottom {
            width: 100%;
            background-color: #fff;
            padding: 20px;
        }
        .modal-bottom h3 {
            color: var(--primary-blue);
            margin-bottom: 15px;
            font-size: 1.3rem;
            text-align: left;
        }
        .map-container {
            width: 100%;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eee;
        }
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        @media (max-width: 768px) {
            .modal-top { flex-direction: column; }
            .modal-left, .modal-right { width: 100%; }
            .modal-left img { height: 250px; }
            .nav-menu { display: none; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="index.php" class="logo"><i class="fa-solid fa-building"></i> &nbsp; RIVER VIEW</a>
        <ul class="nav-menu">
            <li><a href="index.php">หน้าแรก</a></li>
            <li><a href="projects.php" class="active">โครงการ</a></li>
            <li><a href="index.php#facilities">สิ่งอำนวยความสะดวก</a></li>
        </ul>
        <button class="btn-contact">ติดต่อเรา</button>
    </nav>

    <div class="page-header">
        <h1>รูปแบบห้องพักของเรา</h1>
        <p>เลือกพื้นที่ความสุขที่ตอบโจทย์ไลฟ์สไตล์ของคุณ</p>
    </div>

    <section class="section-padding">
        <div class="room-grid">
            <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <img src="<?= htmlspecialchars($room['image_url']) ?>" alt="Room" class="room-img">
                <div class="room-info">
                    <h3><?= htmlspecialchars($room['room_name']) ?></h3>
                    <div class="room-price"><?= htmlspecialchars($room['price_text']) ?></div>
                    <div class="room-specs">
                        <div><i class="fa-solid fa-vector-square"></i> ขนาด: <?= htmlspecialchars($room['room_size']) ?></div>
                        <div style="margin-top: 5px;"><i class="fa-solid fa-bed"></i> <?= htmlspecialchars($room['room_func']) ?></div>
                    </div>
                    <button class="btn-view-room" onclick="openRoomModal(
                        '<?= htmlspecialchars($room['room_name'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($room['price_text'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($room['room_size'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($room['room_func'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($room['image_url'], ENT_QUOTES) ?>'
                    )">ดูรายละเอียดเพิ่มเติม</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div id="roomModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeRoomModal()">&times;</span>
            
            <div class="modal-top">
                <div class="modal-left">
                    <img id="modal-img" src="" alt="Room Image">
                </div>
                <div class="modal-right">
                    <h2 id="modal-title">Room Name</h2>
                    <div class="m-price" id="modal-price">Price</div>
                    <ul>
                        <li><i class="fa-solid fa-vector-square"></i> <strong>พื้นที่ใช้สอย:</strong> <span id="modal-size"></span></li>
                        <li><i class="fa-solid fa-couch"></i> <strong>ฟังก์ชัน:</strong> <span id="modal-func"></span></li>
                        <li><i class="fa-solid fa-check-circle"></i> <strong>ตกแต่ง:</strong> Fully Fitted</li>
                    </ul>
                    <a href="#" class="btn-modal-contact"><i class="fa-brands fa-line"></i> สอบถามโปรโมชั่น</a>
                </div>
            </div>

            <div class="modal-bottom">
                <h3><i class="fa-solid fa-map-marker-alt" style="color: var(--accent-yellow-hover);"></i> ทำเลที่ตั้งโครงการ</h3>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.4578508659163!2d100.5286576148303!3d13.751249990347893!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTPCsDQ1JzA0LjUiTiAxMDDCsDMxJzUxLjEiRQ!5e0!3m2!1sth!2sth!4v1628151234567!5m2!1sth!2sth" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>

        </div>
    </div>

    <script>
        function openRoomModal(title, price, size, func, img) {
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-price').innerText = price;
            document.getElementById('modal-size').innerText = size;
            document.getElementById('modal-func').innerText = func;
            document.getElementById('modal-img').src = img;
            document.getElementById('roomModal').style.display = 'flex';
        }
        function closeRoomModal() {
            document.getElementById('roomModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('roomModal')) { closeRoomModal(); }
        }
    </script>
</body>
</html>