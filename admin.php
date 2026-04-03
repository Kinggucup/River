<?php
ob_start();
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$host = 'sql100.infinityfree.com'; 
$dbname = 'if0_41356232_condo_db'; 
$username = 'if0_41356232'; 
$password = 'Kinggucup0822';     

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // อัปเดตฐานข้อมูลอัตโนมัติ
    try { $conn->exec("ALTER TABLE condo_rooms ADD COLUMN map_url TEXT"); } catch(PDOException $e) {}
    try { $conn->exec("ALTER TABLE condo_rooms ADD COLUMN gallery_images TEXT"); } catch(PDOException $e) {}
    try { $conn->exec("ALTER TABLE condo_rooms ADD COLUMN room_details TEXT"); } catch(PDOException $e) {}
    try { $conn->exec("ALTER TABLE condo_rooms ADD COLUMN is_recommended TINYINT(1) DEFAULT 0"); } catch(PDOException $e) {}
    try { $conn->exec("ALTER TABLE condo_rooms ADD COLUMN price_text_en VARCHAR(255)"); } catch(PDOException $e) {}
    try { $conn->exec("CREATE TABLE IF NOT EXISTS translations (keyword VARCHAR(50) PRIMARY KEY, th TEXT, en TEXT, page_group VARCHAR(50) DEFAULT 'home')"); } catch(PDOException $e) {}
    try { $conn->exec("ALTER TABLE translations ADD COLUMN page_group VARCHAR(50) DEFAULT 'home'"); } catch(PDOException $e) {}

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $page = $_GET['page'] ?? 'hero';

} catch(PDOException $e) { 
    echo "Connection failed: " . $e->getMessage(); 
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการเนื้อหา (Admin Dashboard)</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-bg: #f4f7f6; --sidebar-bg: #0A4DA2; --sidebar-hover: #003375; --accent: #FFC107; --text-dark: #333; }
        body { font-family: 'Prompt', sans-serif; margin: 0; display: flex; height: 100vh; background-color: var(--primary-bg); color: var(--text-dark); overflow: hidden; }
        
        /* Sidebar Basics */
        .sidebar { width: 270px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; flex-shrink: 0; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px; }
        .sidebar-header h2 { margin: 0; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar-header i { color: var(--accent); }
        .menu-category { padding: 15px 20px 10px 25px; font-size: 0.75rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-top: 5px; }
        .nav-link { padding: 12px 25px; color: rgba(255,255,255,0.8); text-decoration: none; display: flex; align-items: center; gap: 15px; transition: 0.3s; border-left: 4px solid transparent; }
        
        /* สไตล์สำหรับเมนูย่อย (Dropdown) */
        .submenu-btn { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 12px 25px; color: rgba(255,255,255,0.85); cursor: pointer; transition: 0.3s; border: none; background: none; font-size: 0.95rem; font-family: 'Prompt'; border-left: 4px solid transparent; }
        .submenu-btn:hover { background-color: var(--sidebar-hover); color: white; }
        .submenu-btn.active-group { border-left-color: var(--accent); color: white; background-color: rgba(0,0,0,0.15); font-weight: 500; }
        .submenu-btn i.fa-chevron-down { font-size: 0.75rem; transition: transform 0.3s; }
        .submenu-btn.open i.fa-chevron-down { transform: rotate(180deg); }
        .submenu-container { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; background-color: #083c7e; }
        .submenu-container.open { max-height: 300px; }
        .submenu-link { padding: 10px 25px 10px 55px; display: block; color: rgba(255,255,255,0.65); text-decoration: none; transition: 0.3s; font-size: 0.85rem; position: relative; }
        .submenu-link::before { content: '•'; position: absolute; left: 35px; color: rgba(255,255,255,0.3); font-size: 1.2rem; line-height: 0.8; }
        .submenu-link:hover, .submenu-link.active { color: var(--accent); }

        /* ✨ โค้ด CSS สำหรับตาราง ปุ่ม และช่องกรอกข้อมูล (ที่หายไป) กลับมาแล้วครับ! ✨ */
        .main-content { flex-grow: 1; padding: 40px; overflow-y: auto; }
        .card-section { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        h1 { margin-top: 0; margin-bottom: 30px; font-size: 1.8rem; color: #1A2530; }
        h3 { color: var(--sidebar-bg); margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        label { font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; color: #555; }
        input[type="text"], input[type="file"], textarea, select { padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Prompt'; width: 100%; box-sizing: border-box; }
        textarea { resize: vertical; min-height: 100px; }
        .btn { padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 1rem; text-decoration: none; display: inline-block; transition: 0.3s; font-family: 'Prompt'; }
        .btn-primary { background-color: var(--sidebar-bg); } .btn-primary:hover { background-color: var(--sidebar-hover); }
        .btn-save { background-color: #198754; } .btn-save:hover { background-color: #157347; }
        .btn-cancel { background-color: #6c757d; margin-left: 10px; }
        .btn-edit { background-color: #ffc107; color: #000; padding: 6px 12px; font-size: 0.9rem; border-radius: 4px; }
        .btn-delete { background-color: #dc3545; padding: 6px 12px; font-size: 0.9rem; border-radius: 4px; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { padding: 15px; border: 1px solid #ddd; text-align: left; } th { background-color: var(--sidebar-bg); color: white; } tr:nth-child(even) { background-color: #f9f9f9; }
        .alert { padding: 15px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 25px; border-left: 5px solid #28a745; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><h2><i class="fa-solid fa-building"></i> Admin Panel</h2></div>

        <div style="padding: 12px 10px; background: rgba(0,0,0,0.15); border-radius: 10px; margin: 10px; text-align: center;">
            <div style="font-size: 1.8rem; color: var(--accent); margin-bottom: 5px;"><i class="fa-solid fa-circle-user"></i></div>
            <strong style="font-size: 0.95rem; color: #fff; font-weight: 500;"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></strong>
        </div>

        <div class="menu-category">จัดการเนื้อหา (Content)</div>

        <?php $is_index_active = in_array($page, ['hero']); ?>
        <div>
            <button class="submenu-btn <?= $is_index_active ? 'active-group open' : '' ?>" onclick="toggleMenu(this)">
                <span style="display:flex; align-items:center; gap:15px;"><i class="fa-solid fa-house" style="width:20px;"></i> หน้าแรก (Index)</span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="submenu-container <?= $is_index_active ? 'open' : '' ?>">
                <a href="admin.php?page=hero" class="submenu-link <?= $page === 'hero' ? 'active' : '' ?>">ตั้งค่าแบนเนอร์ (Hero)</a>
            </div>
        </div>

        <?php $is_project_active = in_array($page, ['rooms']); ?>
        <div>
            <button class="submenu-btn <?= $is_project_active ? 'active-group open' : '' ?>" onclick="toggleMenu(this)">
                <span style="display:flex; align-items:center; gap:15px;"><i class="fa-solid fa-city" style="width:20px;"></i> หน้าโครงการ</span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="submenu-container <?= $is_project_active ? 'open' : '' ?>">
                <a href="admin.php?page=rooms" class="submenu-link <?= $page === 'rooms' ? 'active' : '' ?>">จัดการห้องและแผนที่</a>
            </div>
        </div>

        <?php $is_lang_active = ($page === 'language'); ?>
        <div>
            <button class="submenu-btn <?= $is_lang_active ? 'active-group open' : '' ?>" onclick="toggleMenu(this)">
                <span style="display:flex; align-items:center; gap:15px;"><i class="fa-solid fa-language" style="width:20px;"></i> แปลภาษาหน้าเว็บ</span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="submenu-container <?= $is_lang_active ? 'open' : '' ?>">
                <?php $sec = $_GET['section'] ?? 'home'; ?>
                <a href="admin.php?page=language&section=home" class="submenu-link <?= ($page === 'language' && $sec === 'home') ? 'active' : '' ?>">แปลหน้าหลัก (Home)</a>
                <a href="admin.php?page=language&section=projects" class="submenu-link <?= ($page === 'language' && $sec === 'projects') ? 'active' : '' ?>">แปลหน้าโครงการ (Projects)</a>
            </div>
        </div>

        <a href="index.php" class="nav-link" target="_blank" style="margin-top: auto; color: #fff;"><i class="fa-solid fa-globe"></i> ดูหน้าเว็บจริง</a>
        <a href="login.php?action=logout" class="nav-link" style="color: #ff6b6b; border-top: 1px solid rgba(255,255,255,0.1);" onclick="return confirm('ออกจากระบบ?');"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
    </aside>

    <main class="main-content">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert"><i class="fa-solid fa-check-circle"></i> ทำรายการสำเร็จเรียบร้อย!</div>
        <?php endif; ?>

        <?php
        switch ($page) {
            case 'hero': include 'admin_hero.php'; break;
            case 'rooms': include 'admin_rooms.php'; break;
            case 'language': include 'admin_lang.php'; break;
            default: echo "<h1>หน้าเว็บไม่พบ</h1>"; break;
        }
        ?>
    </main>

    <script>
        function toggleMenu(btn) {
            btn.classList.toggle('open');
            btn.nextElementSibling.classList.toggle('open');
        }
    </script>
</body>
</html>