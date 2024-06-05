<?php
session_start();
require_once '../assets/database/connect.php';

// ดึงข้อมูลผู้ใช้เพียงครั้งเดียว
if (isset($_SESSION['user_login']) || isset($_SESSION['staff_login'])) {
    $user_id = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : $_SESSION['staff_login'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<?php
$searchTitle = "";
$searchValue = "";
$result = [];

if (isset($_GET['search'])) {
    $searchValue = htmlspecialchars($_GET['search']);
    $searchTitle = "ค้นหา \"$searchValue\" | ";
}

try {
    $action = $_GET['action'] ?? 'all';
    $searchQuery = isset($_GET["search"]) && !empty($_GET["search"]) ? "%" . $_GET["search"] . "%" : null;
    $query = "SELECT * FROM crud WHERE 1=1";

    if ($action === 'material') {
        $query .= " AND categories = 'วัสดุ'";
    } elseif ($action === 'equipment') {
        $query .= " AND categories = 'อุปกรณ์'";
    } elseif ($action === 'tools') {
        $query .= " AND categories = 'เครื่องมือ'";
    }

    if ($searchQuery) {
        $query .= " AND (sci_name LIKE :search OR s_number LIKE :search)";
    }

    $query .= " ORDER BY id ASC";
    $stmt = $conn->prepare($query);

    if ($searchQuery) {
        $stmt->bindParam(':search', $searchQuery, PDO::PARAM_STR);
    }

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $nums = count($result); // นับจำนวนรายการ
} catch (PDOException $e) {
    echo 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $searchTitle; ?>จัดการวัสดุ อุปกรณ์ และเครื่องมือ</title>

    <!-- ส่วนของ Link -->
    <link rel="stylesheet" href="../assets/font-awesome/css/all.css">
    <link rel="stylesheet" href="../assets/css/navigator.css">
    <link rel="stylesheet" href="../assets/css/management_systems.css">
    <link rel="stylesheet" href="../assets/css/edit.css">
</head>

<body>
    <!-- Header -->
    <header><?php include('header.php'); ?></header>
    <div class="header_management">
        <div class="header_management_section">
            <div class="header_name_section">
                <a href="../"><i class="fa-solid fa-arrow-left-long"></i></a>
                <span id="B">จัดการระบบ</span>
            </div>
            <div class="header_num_section">
                <span id="B">
                    <?php
                    if ($action === 'all') {
                        echo 'วัสดุ อุปกรณ์ และเครื่องมือทั้งหมด';
                    } elseif ($action === 'material') {
                        echo 'วัสดุทั้งหมด';
                    } elseif ($action === 'equipment') {
                        echo 'อุปกรณ์ทั้งหมด';
                    } elseif ($action === 'tools') {
                        echo 'เครื่องมือทั้งหมด';
                    }
                    echo " $nums รายการ";
                    ?>
                </span>
            </div>
            <div class="header_btn_section">
                <button class="choose_categories_btn management_popup_btn">
                    <i class="icon fa-solid fa-plus"></i>
                    <span>เพิ่มวัสดุ อุปกรณ์ และเครื่องมือ</span>
                </button>
                <!-- POPUP  -->
                <div class="management_popup">
                    <div class="management_content_popup">
                        <div class="management_popup_header">
                            <span id="B">เลือกประเภทที่จะเพิ่มข้อมูล</span>
                            <div class="modalClose" id="closeDetails">
                                <i class="fa-solid fa-xmark"></i>
                            </div>
                        </div>
                        <div class="management_popup_content">
                            <ul>
                                <li>
                                    <a href="add?add=material"><i class="fa-solid fa-flask-vial"></i><span>เพิ่มวัสดุ</span></a>
                                </li>
                                <li>
                                    <a href="add?add=equipment"><i class="fa-solid fa-toolbox"></i><span>เพิ่มอุปกรณ์</span></a>
                                </li>
                                <li>
                                    <a href="add?add=tools"><i class="fa-solid fa-microscope"></i><span>เพิ่มเครื่องมือ</span></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- End POPUP -->
            </div>
        </div>
    </div>
    <div class="management_section_btn">
        <form class="management_search_header" method="get">
            <input type="hidden" name="action" value="<?= htmlspecialchars($action); ?>">
            <input class="search" type="search" name="search" value="<?= htmlspecialchars($searchValue); ?>" placeholder="ค้นหา">
            <button class="search" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <form class="btn_management_all" method="get">
            <button type="submit" class="<?= ($action === 'all') ? 'active' : ''; ?> btn_management_01" name="action" value="all">ทั้งหมด</button>
            <button type="submit" class="<?= ($action === 'material') ? 'active' : ''; ?> btn_management_02" name="action" value="material">วัสดุ</button>
            <button type="submit" class="<?= ($action === 'equipment') ? 'active' : ''; ?> btn_management_02" name="action" value="equipment">อุปกรณ์</button>
            <button type="submit" class="<?= ($action === 'tools') ? 'active' : ''; ?> btn_management_03" name="action" value="tools">เครื่องมือ</button>
        </form>
    </div>
    <div class="management_grid">
        <?php if (empty($result)) { ?>
            <div class="management_grid_not_found">ไม่พบข้อมูล</div>
            <?php } else {
            foreach ($result as $results) { ?>
                <div class="management_grid_content">
                    <div class="management_grid_header">
                        <div class="content_img">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($results['img']); ?>" loading="lazy">
                        </div>
                    </div>
                    <div class="content_status_details">
                        <?php if ($results['amount'] >= 50) { ?>
                            <div class="ready-to-use">
                                <i class="fa-solid fa-circle-check"></i>
                                <span id="B">ปกติ</span>
                            </div>
                        <?php } elseif ($results['amount'] <= 30 && $results['amount'] >= 1) { ?>
                            <div class="moderately">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span id="B">ปานกลาง</span>
                            </div>
                        <?php } elseif ($results['amount'] == 0) { ?>
                            <div class="not-available">
                                <i class="fa-solid fa-ban"></i>
                                <span id="B">ไม่พร้อมใช้งาน</span>
                            </div>
                        <?php } ?>
                        <div class="content_details">
                            <button class="details_btn" data-modal="<?php echo $results['id']; ?>">
                                <i class="fa-solid fa-circle-info"></i>
                            </button>
                        </div>
                        <div class="content_details_popup" id="<?php echo $results['id']; ?>">
                            <div class="details">
                                <div class="details_header">
                                    <span id="B">แก้ไขข้อมูล</span>
                                    <div class="modalClose" id="closeDetails">
                                        <i class="fa-solid fa-xmark"></i>
                                    </div>
                                </div>
                                <form class="details_content_edit" action="update.php" method="post" enctype="multipart/form-data">
                                    <div class="details_content_left">
                                        <div class="img_details">
                                            <div class="img">
                                                <div class="imgInput">
                                                    <img class="previewImg" id="previewImg_<?php echo htmlspecialchars($results['img']); ?>" src="../assets/uploads/<?php echo htmlspecialchars($results['img']); ?>" loading="lazy">
                                                </div>
                                            </div>
                                            <span class="upload-tip"><b>Note: </b>Only JPG, JPEG, PNG & GIF files allowed to upload.</span>
                                            <div class="btn_img">
                                                <label class="choose-file" for="imgInput_<?php echo htmlspecialchars($results['img']); ?>">เลือกรูปภาพที่จะอัพโหลด</label>
                                                <span class="file_chosen_img" id="file-chosen-img_<?php echo htmlspecialchars($results['img']); ?>"><?php echo htmlspecialchars($results['img']); ?></span>
                                            </div>
                                            <input type="file" class="input-img" id="imgInput_<?php echo htmlspecialchars($results['img']); ?>" name="img" accept="image/jpeg, image/png" hidden>
                                            <input type="hidden" value="<?php echo htmlspecialchars($results['img']); ?>" required name="img2">
                                        </div>
                                    </div>
                                    <div class="details_content_right">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($results['id']); ?>">
                                        <ul class="details_content_li">
                                            <li>
                                                <div class="details_content_1"><span id="B">ชื่อ</span></div>
                                                <div class="details_content_2"><input type="text" name="sci_name" value="<?php echo htmlspecialchars($results['sci_name']); ?>"></div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">จำนวน</span></div>
                                                <div class="details_content_2"><input type="number" name="amount" value="<?php echo htmlspecialchars($results['amount']); ?>"></div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">ประเภท</span></div>
                                                <div class="details_content_2">
                                                    <select name="categories">
                                                        <?php
                                                        $categoriesfixes = ['วัสดุ', 'อุปกรณ์', 'เครื่องมือ'];
                                                        foreach ($categoriesfixes as $categoriesfixe) {
                                                            $selected = ($results['categories'] == $categoriesfixe) ? "selected" : "";
                                                            echo "<option value='$categoriesfixe' $selected>$categoriesfixe</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">รุ่น</span></div>
                                                <div class="details_content_2"><span>BK-FD12P</span></div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">ยี่ห้อ</span></div>
                                                <div class="details_content_2"><span>BIOBASE</span></div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">บริษัท</span></div>
                                                <div class="details_content_2"><span>BIOBASE BIODUSTRY(SHANDONG) CO.,LTD</span></div>
                                            </li>
                                        </ul>
                                        <div class="details_content_footer">
                                            <button type="reset">คืนค่าเดิม</button>
                                            <button type="submit" name="update">ยืนยัน</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="management_grid_content_body">
                        <div class="content_name"><span id="B">ชื่อ </span><?php echo htmlspecialchars($results['sci_name']); ?></div>
                        <div class="content_categories"><span id="B">ประเภท </span><?php echo htmlspecialchars($results['categories']); ?></div>
                        <div class="content_amount"><span id="B">คงเหลือ </span><?php echo htmlspecialchars($results['amount']); ?></div>
                    </div>
                    <div class="management_grid_content_footer">
                        <button class="edit_crud_btn details_btn" data-modal="<?php echo $results['id']; ?>">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>แก้ไขข้อมูล</span>
                        </button>
                        <button class="delete_btn management_popup_btn02" data-modal="delete_<?php echo $results['id']; ?>">
                            <i class="icon fa-solid fa-trash"></i>
                            <span>ลบข้อมูล</span>
                        </button>
                        <div class="management_popup02" id="delete_<?php echo $results['id']; ?>">
                            <div class="management_content_popup">
                                <div class="management_popup_header">
                                    <span id="B">ยืนยันการลบ</span>
                                    <div class="modalClose" id="closeDetails">
                                        <i class="fa-solid fa-xmark"></i>
                                    </div>
                                </div>
                                <div class="management_popup_content">
                                    <div class="details_content_left">
                                        <div class="img_details">
                                            <div class="img">
                                                <img class="previewImg" id="previewImg_<?php echo htmlspecialchars($results['img']); ?>" src="../assets/uploads/<?php echo htmlspecialchars($results['img']); ?>" loading="lazy">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="details_content_right">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($results['id']); ?>">
                                        <ul class="details_content_li">
                                            <li>
                                                <div class="details_content_1">
                                                    <span id="B">Serial Number</span>
                                                </div>
                                                <div class="details_content_2">
                                                    <input type="text" name="sci_name" value="<?php echo htmlspecialchars($results['sci_name']); ?>">
                                                </div>
                                            </li>
                                            <li>
                                                <div class="details_content_1">
                                                    <span id="B">ชื่อ</span>
                                                </div>
                                                <div class="details_content_2">
                                                    <input type="text" name="sci_name" value="<?php echo htmlspecialchars($results['sci_name']); ?>">
                                                </div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">จำนวน</span></div>
                                                <div class="details_content_2"><input type="number" name="amount" value="<?php echo htmlspecialchars($results['amount']); ?>"></div>
                                            </li>
                                            <li>
                                                <div class="details_content_1"><span id="B">ประเภท</span></div>
                                                <div class="details_content_2">
                                                    <select name="categories">
                                                        <?php
                                                        $categoriesfixes = ['วัสดุ', 'อุปกรณ์', 'เครื่องมือ'];
                                                        foreach ($categoriesfixes as $categoriesfixe) {
                                                            $selected = ($results['categories'] == $categoriesfixe) ? "selected" : "";
                                                            echo "<option value='$categoriesfixe' $selected>$categoriesfixe</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </li>
                                        </ul>
                                        <div class="choose_categories_footer">
                                            <a href="delete.php?id=<?php echo htmlspecialchars($results['id']); ?>" class="Delete">
                                                <i class="icon fa-solid fa-trash"></i><span>ลบข้อมูล</span>
                                            </a>
                                            <button class="closePOPUP_delete">ปิดหน้าต่าง</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php }
        } ?>
    </div>
    <script src="../assets/js/choose_categories.js"></script>
    <script src="../assets/js/pop_upEdit.js"></script>
</body>

</html>