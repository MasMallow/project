<?php
session_start();
require_once 'assets/database/dbConfig.php';
if (isset($_SESSION['user_login'])) {
    $userID = $_SESSION['user_login'];
    $stmt = $conn->prepare("
        SELECT * 
        FROM users_db
        WHERE userID = :userID
    ");
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        if ($userData['status'] == 'not_approved') {
            unset($_SESSION['user_login']);
            header('Location: auth/sign_in');
            exit();
        }
    }
}

if (isset($_SESSION['staff_login'])) {
    $userID = $_SESSION['staff_login'];
    $stmt = $conn->prepare("
        SELECT * 
        FROM users_db
        WHERE userID = :userID
    ");
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ประกาศตัวแปรเริ่มต้น
$searchValue = '';
$results = [];
$page = '';

// ตรวจสอบว่ามีการส่งค่า search ผ่าน GET มาหรือไม่
if (isset($_GET['search'])) {
    $searchValue = htmlspecialchars($_GET['search']);
}

try {
    sleep(1);
    // กำหนดเงื่อนไขเบื้องต้น
    $sql = "SELECT * FROM crud LEFT JOIN info_sciname ON crud.serial_number = info_sciname.serial_number";
    $conditions = [];
    $params = [];

    if (isset($_GET['page'])) {
        $page = $_GET['page'];
        $validPages = ['material', 'equipment', 'tools'];
        if (in_array($page, $validPages)) {
            switch ($page) {
                case 'material':
                    $category = 'วัสดุ';
                    break;
                case 'equipment':
                    $category = 'อุปกรณ์';
                    break;
                case 'tools':
                    $category = 'เครื่องมือ';
                    break;
            }
            $conditions[] = "categories = :category";
            $params[':category'] = $category;
        }
    }

    if (!empty($searchValue)) {
        $conditions[] = "crud.sci_name LIKE :search";
        $params[':search'] = '%' . $searchValue . '%';
    }

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY RAND() LIMIT 50;";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}
?>
<?php include_once 'assets/includes/header.php' ?>
<header><?php include_once('assets/includes/navigator.php'); ?></header>
<?php if (isset($userData['urole']) && $userData['urole'] == 'user' || empty($userData['urole'])) : ?>
    <main class="content">
        <div class="menu_navigator">
            <ul class="sb_ul">
                <li>
                    <a class="link <?php echo !isset($_GET['page']) && empty($_GET['page']) ? 'active ' : '' ?>" href="<?php echo $base_url; ?>">
                        <i class="icon fa-solid fa-house"></i>
                        <span class="text">หน้าหลัก</span>
                    </a>
                </li>
                <li class="group_li">
                    <a class="group_li_01 <?php echo isset($_GET['page']) && ($_GET['page'] == 'material') ? 'active ' : '' ?>" href="?page=material">
                        <span class="text">ประเภทวัสดุ</span>
                    </a>
                    <a class="group_li_02 <?php echo isset($_GET['page']) && ($_GET['page'] == 'equipment') ? 'active ' : '' ?>" href="?page=equipment">
                        <span class="text">ประเภทอุปกรณ์</span>
                    </a>
                    <a class="group_li_03 <?php echo isset($_GET['page']) && ($_GET['page'] == 'tools') ? 'active ' : '' ?>" href="?page=tools">
                        <span class="text">ประเภทเครื่องมือ</span>
                    </a>
                </li>
                <li>
                    <a class="link" href="<?php echo $base_url; ?>/returned_system">
                        <i class="fa-solid fa-hourglass-end"></i>
                        <span class="text">สิ้นสุดการใช้งาน</span>
                    </a>
                </li>
                <li>
                    <a class="link" href="<?php echo $base_url; ?>/booking_log">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span class="text">ติดตามการจอง</span>
                    </a>
                </li>
                <li>
                    <a class="link" href="<?php echo $base_url; ?>/bookings_list">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <span class="text">ยกเลิกการจอง</span>
                    </a>
                </li>
                <li>
                    <a class="link" href="<?php echo $base_url; ?>/notification">
                        <i class="fa-solid fa-envelope"></i>
                        <span class="text">แจ้งเตือน</span>
                    </a>
                </li>
                <li>
                    <a class="link" href="<?php echo $base_url; ?>/cart_systems">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>รายการที่จอง</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="content_area">
            <div class="content_area_header">
                <form method="get">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($page); ?>">
                    <input class="search" type="search" name="search" value="<?= htmlspecialchars($searchValue); ?>" placeholder="ค้นหา">
                    <button class="search_btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="content_area_nav">
                    <div class="section_2">
                        <div class="date" id="date"></div>
                        <div class="time" id="time"></div>
                    </div>
                </div>
            </div>
            <div class="content_area_all">
                <?php if (empty($results)) : ?>
                    <div class="grid_content_not_found">
                        <span id="B">ไม่พบข้อมูลที่ค้นหา</span>
                    </div>
                <?php else : ?>
                    <div class="content_area_grid">
                        <?php foreach ($results as $data) : ?>
                            <div class="grid_content">
                                <div class="grid_content_header">
                                    <div class="content_img">
                                        <img src="<?php echo $base_url; ?>/assets/uploads/<?= htmlspecialchars($data['img_name']) ?>" alt="Image">
                                    </div>
                                </div>
                                <div class="content_status_details">
                                    <?php if ($data['amount'] >= 50) : ?>
                                        <div class="ready-to-use">
                                            <i class="fa-solid fa-circle-check"></i>
                                            <span id="B">พร้อมใช้งาน</span>
                                        </div>
                                    <?php elseif ($data['amount'] <= 30 && $data['amount'] >= 1) : ?>
                                        <div class="moderately">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <span id="B">ความพร้อมปานกลาง</span>
                                        </div>
                                    <?php elseif ($data['amount'] == 0) : ?>
                                        <div class="not-available">
                                            <i class="fa-solid fa-ban"></i>
                                            <span id="B">ไม่พร้อมใช้งาน</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="content_details">
                                        <button class="details_btn" data-modal="<?= htmlspecialchars($data['ID']) ?>">
                                            <i class="fa-solid fa-circle-info"></i>
                                        </button>
                                    </div>
                                    <div class="content_details_popup" id="<?= htmlspecialchars($data['ID']) ?>">
                                        <div class="details">
                                            <div class="details_header">
                                                <span id="B">รายละเอียด</span>
                                                <div class="modalClose" id="closeDetails">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </div>
                                            </div>
                                            <div class="details_content">
                                                <div class="details_content_li_left">
                                                    <div class="img_details">
                                                        <div class="img">
                                                            <?php if (!empty($data['img_name'])) : ?>
                                                                <div class="imgInput">
                                                                    <img class="previewImg" src="assets/uploads/<?= htmlspecialchars($data['img_name']); ?>" loading="lazy">
                                                                </div>
                                                            <?php else : ?>
                                                                <div class="imgInput">
                                                                    <i class="fa-solid fa-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="details_content_li_right">
                                                    <table class="details_content_table">
                                                        <tr>
                                                            <td class="td_01"><span id="B">Serial Number</span></td>
                                                            <td><?= htmlspecialchars($data['serial_number']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">ชื่อ</span></td>
                                                            <td><?= htmlspecialchars($data['sci_name']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">ประเภท</span></td>
                                                            <td><?= htmlspecialchars($data['categories']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">จำนวน</span></td>
                                                            <td><?= htmlspecialchars($data['amount']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">รุ่น</span></td>
                                                            <td><?= htmlspecialchars($data['model']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">ยี่ห้อ</span></td>
                                                            <td><?= htmlspecialchars($data['brand']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">บริษัท</span></td>
                                                            <td><?= htmlspecialchars($data['company']) ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="td_01"><span id="B">รายละเอียด</span></td>
                                                            <td><?= htmlspecialchars($data['details']) ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="details_content_footer">
                                                <div class="content_btn">
                                                    <?php if ($data['categories'] == 'อุปกรณ์' || $data['categories'] == 'เครื่องมือ') : ?>
                                                        <?php if ($data['amount'] >= 1) : ?>
                                                            <a href="cart_systems?action=add&item=<?= htmlspecialchars($data['sci_name']) ?>" class="used_it">
                                                                <i class="fa-solid fa-address-book"></i>
                                                                <span>ทำการขอใช้</span>
                                                            </a>
                                                        <?php else : ?>
                                                            <div class="not_available">
                                                                <i class="fa-solid fa-check"></i>
                                                                <span>"ไม่พร้อมใช้งาน"</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid_content_body">
                                    <div class="content_name">
                                        <?= htmlspecialchars($data['sci_name']) ?> (<?= htmlspecialchars($data['serial_number']) ?>)
                                    </div>
                                    <div class="content_categories">
                                        <span id="B">ประเภท </span><?= htmlspecialchars($data['categories']) ?>
                                    </div>
                                    <div class="content_amount">
                                        <span id="B">คงเหลือ </span><?= htmlspecialchars($data['amount']) ?>
                                    </div>
                                </div>
                                <div class="grid_content_footer">
                                    <div class="content_btn">
                                        <?php if ($data['amount'] >= 1) : ?>
                                            <a href="cart_systems?action=add&item=<?= htmlspecialchars($data['sci_name']) ?>" class="used_it">
                                                <i class="fa-solid fa-address-book"></i>
                                                <span>ทำการขอใช้</span>
                                            </a>
                                        <?php else : ?>
                                            <div class="not_available">
                                                <i class="fa-solid fa-check"></i>
                                                <span>"ไม่พร้อมใช้งาน"</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php endif; ?>

<?php
if (isset($userData['urole']) && $userData['urole'] == 'staff') {
    include('staff/home.php');
}
?>
<?php include_once 'assets/includes/footer.php' ?>