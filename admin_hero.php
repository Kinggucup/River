<?php
if (!isset($_SESSION['admin_logged_in'])) { exit; }

// สร้างคอลัมน์เก็บรูปรองอัตโนมัติ (ถ้ายังไม่มี)
try { $conn->exec("ALTER TABLE hero_section ADD COLUMN slider_images TEXT"); } catch(PDOException $e) {}

// ==========================================
// จัดการส่วนแบนเนอร์
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_hero') {
    $subtitle = $_POST['subtitle']; $title = $_POST['title']; $description = $_POST['description']; 
    $bg_image = $_POST['existing_hero_image'] ?? '';
    
    // อัปโหลดรูปหลัก (หน้าปก)
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === 0) {
        $ext = pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION); $new_name = 'hero_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $upload_dir . $new_name)) { $bg_image = $upload_dir . $new_name; }
    }

    // อัปโหลดรูปรอง (สไลเดอร์หลายภาพ)
    $slider_images = $_POST['existing_slider'] ?? '[]'; 
    if (isset($_FILES['slider_upload']) && !empty($_FILES['slider_upload']['name'][0])) {
        $uploaded_sliders = [];
        $file_count = count($_FILES['slider_upload']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['slider_upload']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['slider_upload']['name'][$i], PATHINFO_EXTENSION);
                $new_name = 'hero_slide_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['slider_upload']['tmp_name'][$i], $upload_dir . $new_name)) {
                    $uploaded_sliders[] = $upload_dir . $new_name;
                }
            }
        }
        if (count($uploaded_sliders) > 0) { $slider_images = json_encode($uploaded_sliders); }
    }

    $check = $conn->query("SELECT id FROM hero_section WHERE id = 1")->fetch();
    if ($check) { 
        $sql = "UPDATE hero_section SET subtitle=?, title=?, description=?, bg_image=?, slider_images=? WHERE id=1"; 
        $conn->prepare($sql)->execute([$subtitle, $title, $description, $bg_image, $slider_images]);
    } else { 
        $sql = "INSERT INTO hero_section (id, subtitle, title, description, bg_image, slider_images) VALUES (1, ?, ?, ?, ?, ?)"; 
        $conn->prepare($sql)->execute([$subtitle, $title, $description, $bg_image, $slider_images]);
    }
    
    header("Location: admin.php?page=hero&msg=hero_updated"); exit();
}

$hero = $conn->query("SELECT * FROM hero_section LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<h1>จัดการส่วนแบนเนอร์ (Hero Section)</h1>
<div class="card-section">
    <form action="admin.php?page=hero" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_hero">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group"><label>คำโปรย</label><input type="text" name="subtitle" value="<?= htmlspecialchars($hero['subtitle'] ?? '') ?>" required></div>
            <div class="form-group" style="grid-row: span 2;"><label>พาดหัวหลัก</label><textarea name="title" style="height: 125px;" required><?= htmlspecialchars($hero['title'] ?? '') ?></textarea></div>
            
            <div class="form-group" style="background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; border-radius: 4px;">
                <label>1. รูปภาพหลัก (โชว์รูปแรก)</label>
                <input type="file" name="hero_image" accept="image/png, image/jpeg, image/jpg, image/webp">
                <?php if(!empty($hero['bg_image'])): ?>
                    <input type="hidden" name="existing_hero_image" value="<?= htmlspecialchars($hero['bg_image']) ?>">
                    <img src="<?= htmlspecialchars($hero['bg_image']) ?>" style="width: 80px; margin-top: 10px; border-radius: 4px;">
                <?php endif; ?>
            </div>
            
            <div class="form-group" style="background: #fff8e1; padding: 15px; border: 1px dashed #FFC107; border-radius: 4px; grid-column: span 2;">
                <label>2. รูปภาพสไลเดอร์เพิ่มเติม (เลือกได้หลายภาพ)</label>
                <span style="font-size: 0.8rem; color:#888;">*กดลากคลุมหรือกด Ctrl ค้างไว้ เพื่อเลือกหลายไฟล์</span>
                <input type="file" name="slider_upload[]" accept="image/png, image/jpeg, image/jpg, image/webp" multiple>
                <input type="hidden" name="existing_slider" value='<?= htmlspecialchars($hero['slider_images'] ?? '[]', ENT_QUOTES) ?>'>
                <?php 
                    if(!empty($hero['slider_images'])){
                        $slides = json_decode($hero['slider_images'], true);
                        if(is_array($slides) && count($slides) > 0){
                            echo "<div style='margin-top:10px; display:flex; gap:5px; flex-wrap:wrap;'>";
                            foreach($slides as $s){ echo "<img src='{$s}' style='width: 80px; height: 50px; object-fit: cover; border-radius: 4px;'>"; }
                            echo "</div>";
                        }
                    }
                ?>
            </div>
        </div>
        <div class="form-group"><label>รายละเอียด</label><textarea name="description" required><?= htmlspecialchars($hero['description'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-save"><i class="fa-solid fa-save"></i> บันทึกข้อมูล</button>
    </form>
</div>