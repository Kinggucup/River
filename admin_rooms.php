<?php
if (!isset($_SESSION['admin_logged_in'])) { exit; }

// สร้างคอลัมน์เก็บประเภทห้อง (เช่า/ขาย) อัตโนมัติ (ถ้ายังไม่มี)
try { $conn->exec("ALTER TABLE condo_rooms ADD COLUMN room_type VARCHAR(50) DEFAULT 'rent'"); } catch(PDOException $e) {}

// ==========================================
// จัดการห้องพัก
// ==========================================
if (isset($_GET['delete_room'])) {
    $stmt_img = $conn->prepare("SELECT image_url, gallery_images FROM condo_rooms WHERE id = :id");
    $stmt_img->execute([':id' => $_GET['delete_room']]);
    $img_data = $stmt_img->fetch(PDO::FETCH_ASSOC);
    if ($img_data) {
        if (file_exists($img_data['image_url'])) { unlink($img_data['image_url']); }
        $galleries = json_decode($img_data['gallery_images'] ?? '[]', true);
        if (is_array($galleries)) { foreach($galleries as $g_img) { if (file_exists($g_img)) unlink($g_img); } }
    }
    $conn->prepare("DELETE FROM condo_rooms WHERE id = :id")->execute([':id' => $_GET['delete_room']]);
    header("Location: admin.php?page=rooms&msg=room_deleted"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_room') {
    $id = $_POST['id'] ?? null; 
    $room_name = $_POST['room_name']; 
    $room_type = $_POST['room_type']; 
    $price_text = $_POST['price_text']; 
    $room_size = $_POST['room_size']; 
    $room_func = $_POST['room_func']; 
    $room_details = $_POST['room_details'] ?? ''; 
    $map_url = $_POST['map_url'] ?? ''; 
    $image_url = $_POST['existing_image'] ?? '';
    
    // ✨ รับค่าโครงการแนะนำ (ถ้าติ๊กจะส่งค่ามา ถ้าไม่ติ๊กจะเป็น null)
    $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;
    
    // 1. จัดการรูปหน้าปก
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === 0) {
        $ext = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION); 
        $new_name = 'room_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $upload_dir . $new_name)) { 
            if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) { unlink($_POST['existing_image']); }
            $image_url = $upload_dir . $new_name; 
        }
    }

    // 2. จัดการแกลลอรี่ (ระบบเลือกลบรูป)
    $kept_gallery = $_POST['kept_gallery'] ?? []; 

    if (!empty($id)) {
        $stmt_old = $conn->prepare("SELECT gallery_images FROM condo_rooms WHERE id = ?");
        $stmt_old->execute([$id]);
        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
        if ($old_data) {
            $old_gallery = json_decode($old_data['gallery_images'] ?? '[]', true);
            foreach ($old_gallery as $old_img) {
                if (!in_array($old_img, $kept_gallery) && file_exists($old_img)) {
                    unlink($old_img); 
                }
            }
        }
    }

    // อัปโหลดรูปแกลลอรี่ใหม่เพิ่มเติม
    $new_gallery = [];
    if (isset($_FILES['gallery_upload']) && !empty($_FILES['gallery_upload']['name'][0])) {
        $file_count = count($_FILES['gallery_upload']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['gallery_upload']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['gallery_upload']['name'][$i], PATHINFO_EXTENSION);
                $new_name = 'gal_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['gallery_upload']['tmp_name'][$i], $upload_dir . $new_name)) {
                    $new_gallery[] = $upload_dir . $new_name;
                }
            }
        }
    }
    
    // รวมรูปที่เก็บไว้ + รูปที่อัปโหลดใหม่
    $final_gallery = array_merge($kept_gallery, $new_gallery);
    $gallery_images_json = json_encode($final_gallery);
    
    // ✨ อัปเดตคำสั่ง SQL ให้บันทึกค่า is_recommended ด้วย
    if (!empty($id)) {
        $conn->prepare("UPDATE condo_rooms SET room_name=?, room_type=?, price_text=?, room_size=?, room_func=?, room_details=?, map_url=?, image_url=?, gallery_images=?, is_recommended=? WHERE id=?")
             ->execute([$room_name, $room_type, $price_text, $room_size, $room_func, $room_details, $map_url, $image_url, $gallery_images_json, $is_recommended, $id]);
    } else {
        $conn->prepare("INSERT INTO condo_rooms (room_name, room_type, price_text, room_size, room_func, room_details, map_url, image_url, gallery_images, is_recommended) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
             ->execute([$room_name, $room_type, $price_text, $room_size, $room_func, $room_details, $map_url, $image_url, $gallery_images_json, $is_recommended]);
    }
    header("Location: admin.php?page=rooms&msg=room_saved"); exit();
}

$rooms = $conn->query("SELECT * FROM condo_rooms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC); 
$editRoom = null;
if (isset($_GET['edit_room'])) { 
    $stmt = $conn->prepare("SELECT * FROM condo_rooms WHERE id = :id"); 
    $stmt->execute([':id' => $_GET['edit_room']]); 
    $editRoom = $stmt->fetch(PDO::FETCH_ASSOC); 
}
?>

<style>
    .admin-modal-overlay {
        display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px);
    }
    .admin-modal-box {
        background-color: #fff; padding: 30px; border-radius: 12px; width: 90%; max-width: 800px;
        max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        animation: slideDown 0.3s ease-out;
    }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    .btn-close-modal { position: absolute; top: 15px; right: 20px; font-size: 28px; color: #aaa; cursor: pointer; transition: 0.3s; }
    .btn-close-modal:hover { color: #dc3545; }
    
    .gallery-manage-container { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; padding: 10px; background: #fffdf2; border: 1px solid #ffeeba; border-radius: 8px; }
    .gallery-item-edit { position: relative; width: 80px; height: 80px; }
    .gallery-item-edit img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
    .btn-remove-photo {
        position: absolute; top: -8px; right: -8px; background: #ff4d4d; color: white; border: none;
        border-radius: 50%; width: 22px; height: 22px; cursor: pointer; font-size: 12px;
        display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .btn-remove-photo:hover { background: #cc0000; transform: scale(1.1); }
</style>

<div class="header-actions">
    <h1>จัดการข้อมูลห้องและแผนที่ (Rooms & Maps)</h1>
    <button class="btn btn-primary" onclick="openAdminModal()" style="font-size: 1.1rem; padding: 12px 25px;">
        <i class="fa-solid fa-plus-circle"></i> เพิ่มห้องใหม่
    </button>
</div>

<div id="roomFormModal" class="admin-modal-overlay" style="<?= $editRoom ? 'display:flex;' : '' ?>">
    <div class="admin-modal-box">
        <span class="btn-close-modal" onclick="closeAdminModal()">&times;</span>
        <h3 style="margin-top:0; border-bottom:2px solid #eee; padding-bottom:15px;">
            <?= $editRoom ? '<i class="fa-solid fa-pen"></i> กำลังแก้ไขข้อมูลห้อง...' : '<i class="fa-solid fa-plus-circle"></i> เพิ่มห้องใหม่' ?>
        </h3>
        
        <form action="admin.php?page=rooms" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_room">
            <input type="hidden" name="id" value="<?= $editRoom['id'] ?? '' ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                
                <div class="form-group" style="grid-column: span 2; background: #fff8bc; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #856404; font-size: 1.05rem; margin: 0;">
                        <input type="checkbox" name="is_recommended" value="1" <?= (!empty($editRoom['is_recommended']) && $editRoom['is_recommended'] == 1) ? 'checked' : '' ?> style="width: 22px; height: 22px; cursor: pointer;">
                        <i class="fa-solid fa-star"></i> ตั้งเป็น <strong>"โครงการแนะนำ"</strong> (แสดงหน้าแรก)
                    </label>
                </div>

                <div class="form-group"><label>ชื่อห้อง</label><input type="text" name="room_name" value="<?= htmlspecialchars($editRoom['room_name'] ?? '') ?>" required></div>
                <div class="form-group">
                    <label>ประเภท (เช่า / ขาย)</label>
                    <select name="room_type" style="padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Prompt';" required>
                        <option value="rent" <?= ($editRoom['room_type'] ?? '') == 'rent' ? 'selected' : '' ?>>🏠 สำหรับเช่า (For Rent)</option>
                        <option value="sale" <?= ($editRoom['room_type'] ?? '') == 'sale' ? 'selected' : '' ?>>🏢 สำหรับขาย (For Sale)</option>
                    </select>
                </div>
                <div class="form-group"><label>ราคา</label><input type="text" name="price_text" value="<?= htmlspecialchars($editRoom['price_text'] ?? '') ?>" required></div>
                <div class="form-group"><label>ขนาดพื้นที่</label><input type="text" name="room_size" value="<?= htmlspecialchars($editRoom['room_size'] ?? '') ?>" required></div>
                <div class="form-group" style="grid-column: span 2;"><label>ฟังก์ชัน</label><input type="text" name="room_func" value="<?= htmlspecialchars($editRoom['room_func'] ?? '') ?>" required></div>
                <div class="form-group" style="grid-column: span 2;">
                    <label><i class="fa-solid fa-circle-info" style="color:var(--sidebar-bg);"></i> รายละเอียดเพิ่มเติม</label>
                    <textarea name="room_details" style="height: 100px;"><?= htmlspecialchars($editRoom['room_details'] ?? '') ?></textarea>
                </div>

                <div class="form-group" style="grid-column: span 2; background: #eef2f5; padding: 15px; border-radius: 8px;">
                    <label><i class="fa-solid fa-map-location-dot"></i> ลิงก์แผนที่ Google Maps</label>
                    <input type="text" name="map_url" value="<?= htmlspecialchars($editRoom['map_url'] ?? '') ?>">
                </div>

                <div class="form-group" style="background: #f9f9f9; padding: 15px; border: 1px dashed #ccc;">
                    <label>1. รูปหน้าปก</label>
                    <input type="file" name="image_upload" accept="image/*">
                    <?php if($editRoom && !empty($editRoom['image_url'])): ?>
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editRoom['image_url']) ?>">
                        <img src="<?= htmlspecialchars($editRoom['image_url']) ?>" style="width: 80px; margin-top: 10px; border-radius: 4px;">
                    <?php endif; ?>
                </div>

                <div class="form-group" style="background: #fff8e1; padding: 15px; border: 1px dashed #FFC107;">
                    <label>2. แกลลอรี่เพิ่มเติม (เลือกเพิ่มหลายรูปได้)</label>
                    <input type="file" name="gallery_upload[]" accept="image/*" multiple>
                    
                    <?php if($editRoom && !empty($editRoom['gallery_images'])): 
                        $gals = json_decode($editRoom['gallery_images'], true);
                        if(is_array($gals) && count($gals) > 0): ?>
                        <div class="gallery-manage-container">
                            <?php foreach($gals as $g): ?>
                                <div class="gallery-item-edit">
                                    <img src="<?= htmlspecialchars($g) ?>">
                                    <input type="hidden" name="kept_gallery[]" value="<?= htmlspecialchars($g) ?>">
                                    <button type="button" class="btn-remove-photo" onclick="this.parentElement.remove()" title="ลบรูปนี้">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; endif; ?>
                </div>
            </div>
            
            <div style="margin-top: 25px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-save"><i class="fa-solid fa-save"></i> <?= $editRoom ? 'บันทึกการแก้ไข' : 'บันทึกห้องใหม่' ?></button>
                <button type="button" class="btn btn-cancel" onclick="closeAdminModal()">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<div class="card-section">
    <h3><i class="fa-solid fa-list"></i> รายการห้องทั้งหมด</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">ID</th> 
                <th>ภาพหน้าปก</th>
                <th>ชื่อห้อง</th>
                <th>ประเภท</th>
                <th>ราคา</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
            <tr>
                <td style="text-align: center; font-weight: bold; color: #0A4DA2;">#<?= $room['id'] ?></td>
                
                <td><img src="<?= htmlspecialchars($room['image_url'] ?? 'https://via.placeholder.com/60x45') ?>" style="width:60px; height:45px; object-fit:cover; border-radius:4px;"></td>
                <td>
                    <strong><?= htmlspecialchars($room['room_name']) ?></strong>
                    <?php if(!empty($room['is_recommended']) && $room['is_recommended'] == 1): ?>
                        <span style="background: #FFC107; color: #000; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: 8px; font-weight: 600;">
                            <i class="fa-solid fa-star"></i> แนะนำหน้าแรก
                        </span>
                    <?php endif; ?>
                </td>
                <td><?= ($room['room_type'] == 'rent') ? 'ให้เช่า' : 'ขาย' ?></td>
                <td><?= htmlspecialchars($room['price_text']) ?></td>
                <td>
                    <a href="admin.php?page=rooms&edit_room=<?= $room['id'] ?>" class="btn btn-edit">แก้ไข</a>
                    <a href="admin.php?page=rooms&delete_room=<?= $room['id'] ?>" class="btn btn-delete" onclick="return confirm('ลบ?');">ลบ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function openAdminModal() { document.getElementById('roomFormModal').style.display = 'flex'; }
    function closeAdminModal() {
        <?php if($editRoom): ?> window.location.href = 'admin.php?page=rooms';
        <?php else: ?> document.getElementById('roomFormModal').style.display = 'none'; <?php endif; ?>
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('roomFormModal')) { closeAdminModal(); }
    }
</script>