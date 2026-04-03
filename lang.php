<?php
// เริ่มระบบ Session
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// รับค่าภาษาจากการกดปุ่ม
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) { 
    $_SESSION['lang'] = $_GET['lang']; 
}
$current_lang = $_SESSION['lang'] ?? 'th';

// -----------------------------------------
// ดึงคำแปลจากฐานข้อมูล
// -----------------------------------------
$host = 'sql100.infinityfree.com'; 
$dbname = 'if0_41356232_condo_db'; 
$username = 'if0_41356232'; 
$password = 'Kinggucup0822'; 

$t = []; // สร้างตัวแปรเก็บคำแปล
try {
    $conn_lang = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn_lang->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ดึงข้อมูลจากตาราง translations
    $stmt = $conn_lang->query("SELECT keyword, th, en FROM translations");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // เลือกภาษาตามที่ผู้ใช้กด (th หรือ en)
        $t[$row['keyword']] = $row[$current_lang]; 
    }
} catch(PDOException $e) {
    // กรณี Database มีปัญหา จะได้ไม่ขึ้น Error 500
    $t = [
        'menu_home' => 'หน้าแรก',
        'menu_projects' => 'โครงการ',
        'btn_contact' => 'ติดต่อเรา'
    ];
}
?>