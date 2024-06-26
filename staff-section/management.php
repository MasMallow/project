<?php
session_start();
require_once 'assets/database/config.php';
include_once 'assets/includes/thai_date_time.php';

// ดึงข้อมูลผู้ใช้เพียงครั้งเดียว (เพิ่มตรวจสอบค่า session)
if (isset($_SESSION['staff_login']) && !empty($_SESSION['staff_login'])) {
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

$searchTitle = "";
$searchValue = "";
$result = [];

if (!isset($_GET['page'])) {
    $page = 1;
}

// ตรวจสอบและกำหนดค่าการค้นหาและหน้าปัจจุบัน
if (!empty($_GET['search'])) {
    $searchValue = htmlspecialchars($_GET['search']);
    $searchTitle = "ค้นหา \"$searchValue\" | ";
    $searchQuery = "%" . $_GET["search"] . "%";
    $page = intval($_GET['page'] ?? 1);

    // เก็บผลการค้นหาไว้ใน session
    $_SESSION['search_results'] = $searchQuery;
    $_SESSION['search_value'] = $searchValue;
} else {
    // ใช้ผลการค้นหาจาก session ถ้ามี
    $searchQuery = $_SESSION['search_results'] ?? null;
    $searchValue = $_SESSION['search_value'] ?? null;
    $page = intval($_GET['page'] ?? 1);
}

$results_per_page = 1; // เปลี่ยนค่าตามความต้องการ

// คำนวณ offset สำหรับคำสั่ง SQL LIMIT
$offset = ($page - 1) * $results_per_page;

// ตรวจสอบ URI ปัจจุบันเพื่อกำหนด category
$request_uri = $_SERVER['REQUEST_URI'];

// คำสั่ง SQL เพื่อดึงข้อมูล
$query = "SELECT * FROM crud LEFT JOIN info_sciname ON crud.serial_number = info_sciname.serial_number";

// เพิ่มเงื่อนไข categories
if (strpos($request_uri, '/management/material') !== false) {
    $query .= " WHERE crud.categories = 'วัสดุ'";
} elseif (strpos($request_uri, '/management/equipment') !== false) {
    $query .= " WHERE crud.categories = 'อุปกรณ์'";
} elseif (strpos($request_uri, '/management/tools') !== false) {
    $query .= " WHERE crud.categories = 'เครื่องมือ'";
} else {
    $query .= " WHERE 1=1"; // เพิ่มเงื่อนไขให้เป็นจริงเสมอเพื่อให้สามารถเพิ่ม AND ต่อไปได้
}

// เพิ่มเงื่อนไขการค้นหา
if ($searchQuery) {
    $query .= " AND (crud.sci_name LIKE :search OR crud.serial_number LIKE :search)";
}

$query .= " ORDER BY crud.ID ASC LIMIT :offset, :results_per_page";

// ดึงข้อมูลจากฐานข้อมูล
$stmt = $conn->prepare($query);

// bind parameter การค้นหา
if ($searchQuery) {
    $stmt->bindParam(':search', $searchQuery, PDO::PARAM_STR);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':results_per_page', $results_per_page, PDO::PARAM_INT);

$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// นับจำนวนรายการทั้งหมด
$total_records_query = "SELECT COUNT(*) AS total FROM crud LEFT JOIN info_sciname ON crud.serial_number = info_sciname.serial_number";

// เพิ่มเงื่อนไข categories สำหรับนับจำนวน
if (strpos($request_uri, '/management/material') !== false) {
    $total_records_query .= " WHERE crud.categories = 'วัสดุ'";
} elseif (strpos($request_uri, '/management/equipment') !== false) {
    $total_records_query .= " WHERE crud.categories = 'อุปกรณ์'";
} elseif (strpos($request_uri, '/management/tools') !== false) {
    $total_records_query .= " WHERE crud.categories = 'เครื่องมือ'";
} else {
    $total_records_query .= " WHERE 1=1";
}

// เพิ่มเงื่อนไขการค้นหาสำหรับนับจำนวน
if ($searchQuery) {
    $total_records_query .= " AND (crud.sci_name LIKE :search_count OR crud.serial_number LIKE :search_count)";
}

$stmt_count = $conn->prepare($total_records_query);

// bind parameter การค้นหาสำหรับนับจำนวน
if ($searchQuery) {
    $stmt_count->bindParam(':search_count', $searchQuery, PDO::PARAM_STR);
}

$stmt_count->execute();
$total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// ถ้าไม่มีข้อมูลมากกว่าหรือเท่ากับ จำนวนที่กำหนดต่อหน้า ก็ไม่ต้องแสดง pagination
if ($total_records <= $results_per_page) {
    $pagination_display = false;
} else {
    $pagination_display = true;
}


// ลบตัวแปร search_results
unset($search_results);
// ลบค่าใน session ที่ชื่อ search_value
unset($_SESSION['search_value']);

// ดึงข้อมูลการอนุมัติการจอง
$stmt = $conn->prepare("SELECT * FROM logs_management");
$stmt->execute();
$Management = $stmt->fetchAll(PDO::FETCH_ASSOC);
$ManagementCount = count($Management); // นับจำนวนรายการ
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $searchTitle; ?>จัดการวัสดุ อุปกรณ์ และเครื่องมือ</title>
    <link href="<?php echo $base_url; ?>/assets/logo/LOGO.jpg" rel="shortcut icon" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/font-awesome/css/all.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/navigator.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/management_systems.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/notification_popup.css">
</head>

<body>
    <header>
        <?php include_once('assets/includes/navigator.php'); ?>
    </header>
    <div class="Dashboard_Management">
        <?php if (isset($_SESSION['updateData_success'])) : ?>
            <div class="toast">
                <div class="toast_section">
                    <div class="toast_content">
                        <i class="fas fa-solid fa-check check"></i>
                        <div class="toast_content_message">
                            <span class="text text_2"><?php echo $_SESSION['updateData_success']; ?></span>
                        </div>
                        <i class="fa-solid fa-xmark close"></i>
                        <div class="progress"></div>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['updateData_success']); ?>
        <?php endif ?>
        <?php if (isset($_SESSION['updateData_error'])) : ?>
            <div class="toast error">
                <div class="toast_section">
                    <div class="toast_content">
                        <i class="fas fa-solid fa-xmark check error"></i>
                        <div class="toast_content_message">
                            <span class="text text_2"><?php echo $_SESSION['updateData_error']; ?></span>
                        </div>
                        <i class="fa-solid fa-xmark close"></i>
                        <div class="progress error"></div>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['updateData_error']); ?>
        <?php endif ?>
        <?php if (isset($_SESSION['delete_success'])) : ?>
            <div class="toast">
                <div class="toast_section">
                    <div class="toast_content">
                        <i class="fas fa-solid fa-check check"></i>
                        <div class="toast_content_message">
                            <span class="text text_2"><?php echo $_SESSION['delete_success']; ?></span>
                        </div>
                        <i class="fa-solid fa-xmark close"></i>
                        <div class="progress"></div>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['delete_success']); ?>
        <?php endif ?>
        <div class="header_management_section">
            <div class="header_name_section">
                <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left-long"></i></a>
                <span id="B">จัดการระบบ</span>
            </div>
            <div class="header_num_section">
                <span>
                    <?php
                    if ($request_uri === '/management') {
                        echo 'วัสดุ อุปกรณ์ และเครื่องมือทั้งหมด';
                    } elseif ($request_uri === '/management/material') {
                        echo 'วัสดุทั้งหมด';
                    } elseif ($request_uri === '/management/equipment') {
                        echo 'อุปกรณ์ทั้งหมด';
                    } elseif ($request_uri === '/management/tools') {
                        echo 'เครื่องมือทั้งหมด';
                    }
                    echo " $total_records รายการ";
                    ?>
                </span>
            </div>
            <div class="choose_categories_btn">
                <a href="<?php echo $base_url; ?>/management/addData">
                    <span>เพิ่มวัสดุ อุปกรณ์ และเครื่องมือ</span>
                </a>
                <?php if ($request_uri == '/management') : ?>
                    <a href="<?php echo $base_url; ?>/management/viewlog">
                        <span>ดูระบบ</span>
                    </a>
                <?php elseif ($request_uri == '/management/viewlog') : ?>
                    <a href="<?php echo $base_url; ?>/management">
                        <span>เพิ่มวัสดุ อุปกรณ์ และเครื่องมือ</span>
                    </a>
                <?php endif ?>
            </div>
        </div>

        <?php if ($request_uri == '/management') : ?>
            <!-- ----------------- BTN SECTION ------------------- -->
            <div class="management_section_btn">
                <form class="management_search_header" method="get">
                    <input class="search_input" type="search" name="search" value="<?= htmlspecialchars($searchValue); ?>" placeholder="ค้นหา">
                    <button class="search_btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <form class="btn_management_all">
                    <a href="/management" class="<?= (strpos($request_uri, '/management') === 0 && strpos($request_uri, '/management/material') === false && strpos($request_uri, '/management/equipment') === false && strpos($request_uri, '/management/tools') === false && strpos($request_uri, '?') === false) ? 'active' : ''; ?> btn_management_01">ทั้งหมด</a>
                    <a href="/management/material" class="<?= (strpos($request_uri, '/management/material') === 0) ? 'active' : ''; ?> btn_management_02">วัสดุ</a>
                    <a href="/management/equipment" class="<?= (strpos($request_uri, '/management/equipment') === 0) ? 'active' : ''; ?> btn_management_02">อุปกรณ์</a>
                    <a href="/management/tools" class="<?= (strpos($request_uri, '/management/tools') === 0) ? 'active' : ''; ?> btn_management_03">เครื่องมือ</a>
                </form>
            </div>
            <?php if (empty($result)) : ?>
                <div class="management_found">
                    <i class="icon fa-solid fa-xmark"></i>
                    <span id="B">ไม่พบรายการวัสดุ อุปกรณ์ และเครื่องมือในระบบ</span>
                </div>
            <?php else : ?>
                <div class="management_grid">
                    <?php foreach ($result as $results) : ?>
                        <div class="management_grid_content">
                            <div class="management_grid_header">
                                <div class="content_img">
                                    <img src="<?php echo $base_url; ?>/assets/uploads/<?php echo htmlspecialchars($results['img_name']); ?>" loading="lazy">
                                </div>
                            </div>
                            <div class="content_status_details">
                                <?php if ($results['availability'] == 0) : ?>
                                    <div class="ready-to-use">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span id="B">พร้อมใช้งาน</span>
                                    </div>
                                <?php elseif ($results['availability'] != 0) : ?>
                                    <div class="moderately">
                                        <i class="fa-solid fa-ban"></i>
                                        <span id="B">บำรุงรักษา</span>
                                    </div>
                                <?php endif ?>
                                <div class="content_details">
                                    <a href="management/detailsData?id=<?= $results['ID'] ?>" class="details_btn">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="management_grid_content_body">
                                <div class="content_name">
                                    <?php echo htmlspecialchars($results['sci_name']); ?></div>
                                <div class="content_categories">
                                    <span id="B">ประเภท </span><?php echo htmlspecialchars($results['categories']); ?>
                                </div>
                                <div class="content_amount">
                                    <span id="B">จำนวน </span><?php echo htmlspecialchars($results['amount']); ?>
                                </div>
                            </div>
                            <div class="management_grid_content_footer">
                                <a href="<?php echo $base_url; ?>/management/editData?id=<?= $results['ID'] ?>" class="edit_crud_btn">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>แก้ไขข้อมูล</span>
                                </a>
                                <a href="<?php echo $base_url; ?>/management/detailsData?id=<?= $results['ID'] ?>" class="delete_btn">
                                    <i class="icon fa-solid fa-trash"></i>
                                    <span>ลบข้อมูล</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>

                <!-- PAGINATION PAGE -->
                <?php if ($pagination_display) : ?>
                    <div class="pagination">
                        <?php if ($page > 1) : ?>
                            <a href="?page=1<?php echo $searchValue ? '&search=' . $searchValue : ''; ?>">&laquo;</a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $searchValue ? '&search=' . $searchValue : ''; ?>">&lsaquo;</a>
                        <?php endif; ?>

                        <?php
                        $total_pages = ceil($total_records / $results_per_page);
                        for ($i = 1; $i <= $total_pages; $i++) {
                            if ($i == $page) {
                                echo "<a class='active'>$i</a>";
                            } else {
                                echo "<a href='?page=$i" . ($searchValue ? '&search=' . $searchValue : '') . "'>$i</a>";
                            }
                        }
                        ?>

                        <?php if ($page < $total_pages) : ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $searchValue ? '&search=' . $searchValue : ''; ?>">&rsaquo;</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $searchValue ? '&search=' . $searchValue : ''; ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($request_uri == '/management/viewlog') : ?>
                <?php if (!empty($Management)) : ?>
                    <div class="viewLog_Management_PAGE">
                        <div class="viewLog_Management_MAIN">
                            <div class="viewLog_Management_header">
                                <span id="B">การจัดการระบบคลัง</span>
                            </div>
                            <div class="viewLog_Management_body">
                                <?php foreach ($Management as $Data) : ?>
                                    <div class="viewLog_Management_content">
                                        <div class="viewLog_User_content_1">
                                            <i class="open_expand_row fa-solid fa-circle-arrow-right"></i>
                                            <a href="<?= $base_url . '/management/viewlog/details?id=' . htmlspecialchars($Data['ID'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($Data['log_Name'], ENT_QUOTES, 'UTF-8') ?>
                                                ( <?= htmlspecialchars($Data['log_Role'], ENT_QUOTES, 'UTF-8') ?>) </a>
                                        </div>
                                        <div class="viewLog_User_content_2">
                                            <?= thai_date_time_2(htmlspecialchars($Data['log_Date'], ENT_QUOTES, 'UTF-8')) ?>
                                        </div>
                                        <div class="viewLog_User_content_3">
                                            ได้ทำการ
                                            <?php
                                            switch ($Data['log_Status']) {
                                                case 'Add':
                                                    echo "เพิ่มข้อมูล";
                                                    break;
                                                case 'Edit':
                                                    echo "แก้ไขข้อมูล";
                                                    break;
                                                case 'Delete':
                                                    echo "ลบข้อมูล";
                                                    break;
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="viewNotfound">
                        <i class="fa-solid fa-database"></i>
                        <span id="B">ไม่พบข้อมูล</span>
                    </div>
                <?php endif; ?>
            <?php elseif ($request_uri == '/management/view_log/details') : ?>
                <?php
                try {
                    if (isset($_GET['id'])) {
                        $id = (int)$_GET['id'];
                        $stmt = $conn->prepare("SELECT * FROM logs_management WHERE ID = :id");
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $stmt->execute();
                        $detailsManagement = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (PDOException $e) {
                    echo 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                    exit;
                } ?>
                <?php if (!empty($detailsManagement)) : ?>
                    <div class="viewLog_Management_Details">
                        <div class="viewLog_Management_MAIN">
                            <div class="viewLog_Management_header">
                                <div class="path-indicator">
                                    <a href="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                            </div>
                            <div class="viewLog_Management_body">
                                <?php foreach ($detailsManagement as $Data) : ?>
                                    <div class="viewLog_Management_content">
                                        <div class="viewLog_Management_content_1">
                                            <?= htmlspecialchars($Data['log_Name'], ENT_QUOTES, 'UTF-8') ?>
                                            (<?= htmlspecialchars($Data['log_Role'], ENT_QUOTES, 'UTF-8') ?>)
                                            <?= thai_date_time_2(htmlspecialchars($Data['log_Date'], ENT_QUOTES, 'UTF-8')) ?>
                                            ได้ทำการ
                                            <?php
                                            switch ($Data['log_Status']) {
                                                case 'Add':
                                                    echo "เพิ่มข้อมูล";
                                                    break;
                                                case 'Edit':
                                                    echo "แก้ไขข้อมูล";
                                                    break;
                                                case 'Delete':
                                                    echo "ลบข้อมูล";
                                                    break;
                                            }
                                            ?>
                                        </div>
                                        <div class="viewLog_Management_content_2">
                                            <?php
                                            $logContent = json_decode($Data['log_Content'], true);
                                            if ($logContent) : ?>
                                                <div class="log-item"><span id="B">ชื่อวิทยาศาสตร์</span> <?= htmlspecialchars($logContent['sci_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="log-item"><span id="B">หมายเลขซีเรียล</span> <?= htmlspecialchars($logContent['serial_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php else : ?>
                                                ไม่สามารถแสดงข้อมูลได้
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="viewNotfound">
                        <i class="fa-solid fa-database"></i>
                        <span id="B">ไม่พบข้อมูล</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
    </div>
</body>

</html>