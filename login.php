<?php
session_start();

// --- 1. ตั้งค่าการเชื่อมต่อฐานข้อมูล ---
$host = 'sql100.infinityfree.com'; 
$dbname = 'if0_41356232_condo_db'; 
$username = 'if0_41356232'; 
$password = 'Kinggucup0822'; 

// --- 2. ระบบออกจากระบบ (Logout) ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// --- 3. ถ้าเข้าสู่ระบบอยู่แล้ว ให้เด้งไปหน้า admin.php เลย ---
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit;
}

$error_msg = '';

// --- 4. เมื่อมีการกดปุ่ม "เข้าสู่ระบบ" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $user_input = $_POST['username'] ?? '';
        $pass_input = $_POST['password'] ?? '';

        // ดึงข้อมูลผู้ใช้จาก Database
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$user_input]);
        $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✨ แก้ไขจุดนี้: นำ md5() ออก เพื่อเทียบรหัสผ่านตรงๆ ✨
        if ($admin_data && $pass_input === $admin_data['password']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin_data['username'];
            // ตรวจสอบชื่อคอลัมน์ใน DB ด้วยนะครับ ถ้าใน DB เป็น Full_Name ต้องแก้ให้ตรงกัน
            $_SESSION['admin_fullname'] = $admin_data['full_name']; 
            
            header("Location: admin.php");
            exit;
        } else {
            $error_msg = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง!";
        }
    } catch(PDOException $e) {
        $error_msg = "เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        h2 { color: #0A4DA2; margin-top: 0; margin-bottom: 25px; font-size: 1.8rem; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Prompt', sans-serif; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #0A4DA2; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; font-family: 'Prompt', sans-serif; font-weight: 600; transition: 0.3s; }
        button:hover { background: #003375; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        
        <?php if ($error_msg): ?>
            <div class="error"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน (Password)</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>