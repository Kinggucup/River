<?php
if (!isset($_SESSION['admin_logged_in'])) { exit; }

$section = $_GET['section'] ?? 'home';
$section_name = ($section === 'home') ? 'หน้าหลัก (Index)' : 'หน้าโครงการ (Projects)';
$filter = $_GET['filter'] ?? 'all';

// ✨ ดึงข้อมูลห้อง "ทั้งหมดและทุกคอลัมน์" มาเตรียมไว้ เพื่อเอามาใส่เป็นค่าเริ่มต้นให้ช่องภาษาไทย
$all_rooms = [];
try { 
    $all_rooms = $conn->query("SELECT * FROM condo_rooms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC); 
} catch(PDOException $e) {}

// คำศัพท์พื้นฐาน
if ($section === 'home') {
    $default_words = [
        'menu_home' => ['หน้าแรก', 'Home'],
        'menu_projects' => ['โครงการ', 'Projects'],
        'menu_recommended' => ['โครงการแนะนำ', 'Recommended Projects'],
        'btn_contact' => ['ติดต่อเรา', 'Contact Us'],
        'btn_view_details' => ['ดูรายละเอียด', 'View Details']
    ];
} else {
    $default_words = [
        'project_title' => ['โครงการทั้งหมด', 'All Projects'],
        'filter_all' => ['ทั้งหมด', 'All'],
        'filter_rent' => ['เช่า', 'Rent'],
        'filter_sale' => ['ขาย', 'Sale'],
        'search_placeholder' => ['ค้นหาโครงการ...', 'Search projects...']
    ];
}

foreach ($default_words as $k => $v) {
    $check = $conn->prepare("SELECT COUNT(*) FROM translations WHERE keyword = ?");
    $check->execute([$k]);
    if ($check->fetchColumn() == 0) {
        $stmt = $conn->prepare("INSERT INTO translations (keyword, th, en, page_group) VALUES (?, ?, ?, ?)");
        $stmt->execute([$k, $v[0], $v[1], $section]);
    }
}

// บันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_lang') {
    foreach ($_POST['th'] as $keyword => $th_text) {
        $en_text = $_POST['en'][$keyword] ?? '';
        
        if (trim($th_text) !== '' || trim($en_text) !== '') {
            $check = $conn->prepare("SELECT COUNT(*) FROM translations WHERE keyword = ?");
            $check->execute([$keyword]);
            if ($check->fetchColumn() > 0) {
                $stmt = $conn->prepare("UPDATE translations SET th = ?, en = ? WHERE keyword = ?");
                $stmt->execute([$th_text, $en_text, $keyword]);
            } else {
                $stmt = $conn->prepare("INSERT INTO translations (keyword, th, en, page_group) VALUES (?, ?, ?, ?)");
                $stmt->execute([$keyword, $th_text, $en_text, $section]);
            }
        } else {
            if(!array_key_exists($keyword, $default_words)){
                 $stmt = $conn->prepare("DELETE FROM translations WHERE keyword = ?");
                 $stmt->execute([$keyword]);
            }
        }
    }
    $f = $_POST['current_filter'] ?? 'all';
    header("Location: admin.php?page=language&section=$section&filter=$f&msg=lang_saved"); 
    exit();
}

// ดึงข้อมูลแปลที่มีอยู่ในฐานข้อมูล
$stmt = $conn->prepare("SELECT * FROM translations WHERE page_group = ?");
$stmt->execute([$section]);
$trans_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$t_dict = [];
foreach($trans_data as $row) { $t_dict[$row['keyword']] = $row; }

// ฟังก์ชันช่วยดึงค่ามาแสดง
function getVal($keyword, $lang, $t_dict, $default_words) {
    if (isset($t_dict[$keyword])) { return $t_dict[$keyword][$lang]; }
    if (isset($default_words[$keyword])) { return ($lang == 'th') ? $default_words[$keyword][0] : $default_words[$keyword][1]; }
    return '';
}
?>

<div class="header-actions">
    <h1><i class="fa-solid fa-language"></i> จัดการคำแปล: <?= $section_name ?></h1>
    <p style="color: #666;">ไม่ต้องกังวลเรื่องการตั้งรหัสคำอีกต่อไป เลือกว่าจะแปลส่วนไหนจากกล่องด้านล่าง ระบบจะเตรียมช่องให้คุณอัตโนมัติ!</p>
</div>

<div style="background: #f1f8ff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #cce5ff; display: flex; align-items: center; gap: 15px;">
    <label style="font-weight: bold; color: #004085; font-size: 1.1rem;"><i class="fa-solid fa-filter"></i> เลือกว่าต้องการแปลส่วนไหน:</label>
    <select onchange="window.location.href='admin.php?page=language&section=<?= $section ?>&filter='+this.value" style="padding: 10px 15px; border-radius: 4px; border: 1px solid #b8daff; font-family: 'Prompt'; font-size: 1.05rem; font-weight: bold; color: #0A4DA2; flex-grow: 1; max-width: 500px; cursor: pointer;">
        <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>🌟 แสดงคำแปลทั้งหมด</option>
        <option value="general" <?= $filter == 'general' ? 'selected' : '' ?>>⚙️ คำศัพท์ทั่วไป (หน้าเว็บ / เมนู / ปุ่ม)</option>
        <?php foreach($all_rooms as $r): ?>
            <option value="room_<?= $r['id'] ?>" <?= $filter == 'room_'.$r['id'] ? 'selected' : '' ?>>🚪 ข้อมูลห้อง: <?= htmlspecialchars($r['room_name']) ?> (ID: <?= $r['id'] ?>)</option>
        <?php endforeach; ?>
    </select>
</div>

<div class="card-section" style="padding-bottom: 80px;">
    <form action="admin.php?page=language&section=<?= $section ?>" method="POST">
        <input type="hidden" name="action" value="save_lang">
        <input type="hidden" name="current_filter" value="<?= htmlspecialchars($filter) ?>">
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width: 25%; background: #0A4DA2; color: white; padding: 12px; text-align: left;">ตำแหน่ง (Keyword)</th>
                    <th style="width: 35%; background: #0A4DA2; color: white; padding: 12px; text-align: left;">🇹🇭 ภาษาไทย (TH)</th>
                    <th style="width: 40%; background: #0A4DA2; color: white; padding: 12px; text-align: left;">🇬🇧 ภาษาอังกฤษ (EN)</th>
                </tr>
            </thead>
            <tbody>
                
                <?php 
                // ==========================================
                // 1. หมวดคำทั่วไป
                // ==========================================
                if ($filter === 'all' || $filter === 'general'): 
                    $general_keys = [];
                    foreach($t_dict as $k => $v) { if (!preg_match('/_\d+$/', $k)) $general_keys[] = $k; }
                    $general_keys = array_unique(array_merge($general_keys, array_keys($default_words)));
                    sort($general_keys);
                ?>
                    <tr style="background-color: #e9ecef;">
                        <td colspan="3" style="padding: 12px 15px; font-weight: bold; color: #495057;">
                            <i class="fa-solid fa-layer-group"></i> คำศัพท์ทั่วไป
                        </td>
                    </tr>
                    <?php foreach($general_keys as $k): ?>
                        <?= renderRow($k, getVal($k, 'th', $t_dict, $default_words), getVal($k, 'en', $t_dict, $default_words), true) ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php 
                // ==========================================
                // 2. หมวดข้อมูลห้อง (ดึงข้อมูลเดิมมาใส่เป็น Default)
                // ==========================================
                foreach ($all_rooms as $r): 
                    $r_id = $r['id'];
                    if ($filter === 'all' || $filter === 'room_'.$r_id):
                        // ✨ เก็บค่าดั้งเดิมจากฐานข้อมูล เพื่อเอาไปโชว์รอให้กดแปล
                        $room_keys = [
                            'room_name_'.$r_id => ['label' => 'ชื่อห้อง', 'fallback' => $r['room_name']],
                            'room_price_'.$r_id => ['label' => 'ราคา', 'fallback' => $r['price_text']],
                            'room_size_'.$r_id => ['label' => 'ขนาดพื้นที่', 'fallback' => $r['room_size']],
                            'room_func_'.$r_id => ['label' => 'ฟังก์ชันห้อง', 'fallback' => $r['room_func']],
                            'room_details_'.$r_id => ['label' => 'รายละเอียดเพิ่มเติม', 'fallback' => $r['room_details']]
                        ];
                ?>
                    <tr style="background-color: #fff3cd; border-top: 2px solid #ffeeba;">
                        <td colspan="3" style="padding: 12px 15px; font-weight: bold; color: #856404;">
                            <i class="fa-solid fa-door-open"></i> ข้อมูลห้องพัก: <?= htmlspecialchars($r['room_name']) ?>
                        </td>
                    </tr>
                    <?php foreach($room_keys as $k => $info): 
                        // ถ้าเคยเซฟคำแปลไว้ ให้ใช้คำที่เคยแปล แต่ถ้ายังไม่เคยแปล ให้ดึงค่า fallback จากระบบมาโชว์
                        $existing_th = getVal($k, 'th', $t_dict, []);
                        $th_val = ($existing_th !== '') ? $existing_th : $info['fallback'];
                        $en_val = getVal($k, 'en', $t_dict, []);
                    ?>
                        <?= renderRow($k, $th_val, $en_val, false, $info['label']) ?>
                    <?php endforeach; ?>
                <?php 
                    endif;
                endforeach; 
                ?>

            </tbody>
        </table>
        
        <div style="position: fixed; bottom: 20px; right: 40px; background: white; padding: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); border-radius: 8px; z-index: 999; border: 2px solid #0A4DA2;">
            <button type="submit" class="btn btn-save" style="padding: 12px 40px; font-size: 1.1rem;">
                <i class="fa-solid fa-save"></i> บันทึกคำแปลที่พิมพ์
            </button>
        </div>
    </form>
</div>

<?php function renderRow($keyword, $th_val, $en_val, $is_general, $label = '') { 
    // เช็กว่านี่คือช่อง "รายละเอียดเพิ่มเติม" หรือไม่
    $is_details = (strpos($keyword, 'details') !== false);
?>
<tr>
    <td style="padding: 12px; border-bottom: 1px solid #eee; background: #f9f9f9; vertical-align: top;">
        <code style="color: #d63384; font-weight: bold; font-size: 1rem;"><?= htmlspecialchars($keyword) ?></code>
        <?php if($label): ?><div style="font-size: 0.8rem; color: #666; margin-top: 5px;"><i class="fa-solid fa-tag"></i> หัวข้อ: <strong><?= $label ?></strong></div><?php endif; ?>
    </td>
    <td style="padding: 12px; border-bottom: 1px solid #eee; vertical-align: top;">
        <?php if($is_details): ?>
            <textarea id="th_<?= $keyword ?>" name="th[<?= $keyword ?>]" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; height: 100px; font-family: 'Prompt'; resize: vertical;"><?= htmlspecialchars($th_val) ?></textarea>
        <?php else: ?>
            <input type="text" id="th_<?= $keyword ?>" name="th[<?= $keyword ?>]" value="<?= htmlspecialchars($th_val) ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Prompt';">
        <?php endif; ?>
    </td>
    <td style="padding: 12px; border-bottom: 1px solid #eee; vertical-align: top;">
        <div style="display: flex; gap: 10px; align-items: flex-start;">
            <?php if($is_details): ?>
                <textarea id="en_<?= $keyword ?>" name="en[<?= $keyword ?>]" placeholder="กรอกภาษาอังกฤษ" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; height: 100px; font-family: 'Prompt'; resize: vertical;"><?= htmlspecialchars($en_val) ?></textarea>
            <?php else: ?>
                <input type="text" id="en_<?= $keyword ?>" name="en[<?= $keyword ?>]" value="<?= htmlspecialchars($en_val) ?>" placeholder="กรอกภาษาอังกฤษ" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Prompt';">
            <?php endif; ?>
            
            <button type="button" onclick="autoTranslate('<?= $keyword ?>')" style="background: #17a2b8; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-family: 'Prompt'; white-space: nowrap; transition: 0.3s; height: fit-content;" onmouseover="this.style.background='#138496'" onmouseout="this.style.background='#17a2b8'" title="แปลอัตโนมัติ">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Auto
            </button>
        </div>
    </td>
</tr>
<?php } ?>

<script>
async function autoTranslate(keyword) {
    const thInput = document.getElementById('th_' + keyword);
    const enInput = document.getElementById('en_' + keyword);
    const textToTranslate = thInput.value.trim();

    if (textToTranslate === '' || textToTranslate.includes('?')) {
        alert('กรุณาพิมพ์ภาษาไทยให้ถูกต้องก่อนกดแปลครับ!'); return;
    }

    const originalEnText = enInput.value;
    enInput.value = "กำลังแปล... ⏳";

    try {
        const response = await fetch(`https://api.mymemory.translated.net/get?q=${encodeURIComponent(textToTranslate)}&langpair=th|en`);
        const data = await response.json();

        if (data.responseData && data.responseData.translatedText) {
            enInput.value = data.responseData.translatedText;
        } else {
            enInput.value = originalEnText;
            alert('แปลไม่ได้ครับ ลองพิมพ์คำใหม่ดูนะครับ');
        }
    } catch (error) {
        console.error("Translation Error:", error);
        enInput.value = originalEnText;
        alert('เชื่อมต่อระบบแปลภาษาไม่ได้ครับ');
    }
}
</script>