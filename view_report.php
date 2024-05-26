<?php
// เริ่มต้นเซสชั่น
session_start();
// เชื่อมต่อกับฐานข้อมูล
require_once 'assets/database/connect.php';
include_once 'includes/thai_date_time.php';

// ตรวจสอบการเข้าสู่ระบบของผู้ใช้
if (isset($_SESSION['user_login']) || isset($_SESSION['staff_login'])) {
    // ถ้าผู้ใช้เข้าสู่ระบบด้วย user_login หรือ staff_login
    $user_id = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : $_SESSION['staff_login'];
    // เตรียมคำสั่ง SQL เพื่อดึงข้อมูลผู้ใช้
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    // ผูกค่า user_id เข้ากับคำสั่ง SQL
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    // ดำเนินการคำสั่ง SQL
    $stmt->execute();
    // ดึงข้อมูลผู้ใช้
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// สร้างคำสั่ง SQL สำหรับดึงข้อมูลการยืมสินค้าที่รอการอนุมัติ
$sql = "SELECT * FROM waiting_for_approval WHERE 1=1";
$params = [];

// ตรวจสอบว่า user_id ถูกส่งมาหรือไม่ และไม่ใช่ค่า 'all'
if (isset($_GET['user_id']) && $_GET['user_id'] !== 'all') {
    // เพิ่มเงื่อนไข user_id ในคำสั่ง SQL
    $sql .= " AND udi = :user_id";
    // เพิ่มค่า user_id ในอาร์เรย์ params
    $params[':user_id'] = (int)$_GET['user_id'];
}

// ตรวจสอบว่ามีการส่งค่า start_date และ end_date มาหรือไม่
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // เพิ่มเงื่อนไขช่วงเวลาในคำสั่ง SQL
    $sql .= " AND borrowdatetime BETWEEN :start_date AND :end_date";
    // เพิ่มค่า start_date และ end_date ในอาร์เรย์ params
    $params[':start_date'] = $_GET['start_date'];
    $params[':end_date'] = $_GET['end_date'];
}

// เตรียมคำสั่ง SQL
$stmt = $conn->prepare($sql);

// ผูกค่าพารามิเตอร์เข้ากับคำสั่ง SQL
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}

// ดำเนินการคำสั่ง SQL
$stmt->execute();
// ดึงข้อมูลการยืมสินค้าที่รอการอนุมัติ
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการยืมสินค้า</title>
    <!-- ลิงก์ไปยังไฟล์ CSS -->
    <link rel="stylesheet" href="assets/font-awesome/css/all.css">
    <link rel="stylesheet" href="assets/css/navigator.css">
    <link rel="stylesheet" href="assets/css/view_report.css">
</head>

<body>
    <header>
        <?php include 'includes/header.php'; ?>
    </header>
    <div class="header_approve">
        <div class="header_approve_section">
            <a href="../project/"><i class="fa-solid fa-arrow-left-long"></i></a>
            <span id="B">รายงานการยืมอุปกรณ์</span>
        </div>
    </div>
    <div class="view_report">
        <div class="view_report_header">
            <span id="B">ค้นหา</span>
        </div>
        <div class="view_report_form">
            <!-- ฟอร์มสำหรับค้นหาการยืมสินค้า -->
            <form class="form_1" action="view_report.php" method="GET">
                <div class="view_report_column">
                    <div class="view_report_input">
                        <label id="B" for="userID">UID ของผู้ใช้งาน</label>
                        <input type="text" id="userID" name="user_id" placeholder="กรอกไอดีผู้ใช้">
                    </div>
                    <div class="view_report_input">
                        <label id="B" for="startDate">ช่วงเวลาเริ่มต้น</label>
                        <input type="date" id="startDate" name="start_date">
                    </div>
                    <div class="view_report_input">
                        <label id="B" for="endDate">ช่วงเวลาสิ้นสุด</label>
                        <input type="date" id="endDate" name="end_date">
                    </div>
                </div>
                <div class="view_report_btn">
                    <button type="submit" class="search">ค้นหา</button>
                </div>
            </form>
        </div>
        <div class="view_report_table">
            <div class="view_report_table_header">
                <span id="B">
                    ประวัติการขอใช้
                </span>
                <form class="form_2" action="view_report" method="GET">
                    <button type="submit" class="reset">แสดงข้อมูลทั้งหมด</button>
                </form>
            </div>
            <table class="view_report_table_data">
                <thead>
                    <tr>
                        <th class="UID" id="B">รหัสผู้ใช้</th>
                        <th class="name" id="B">ชื่อ - นามสกุล</th>
                        <th class="list" id="B">รายการการขอใช้</th>
                        <th class="start_date" id="B">วันเวลาที่ขอใช้</th>
                        <th class="end_date" id="B">วันเวลาสิ้นสุดการขอใช้</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data) > 0) : ?>
                        <?php foreach ($data as $row) : ?>
                            <tr>
                                <td class="UID"><?php echo htmlspecialchars($row["udi"]); ?></td>
                                <td><?php echo htmlspecialchars($row["firstname"]); ?></td>
                                <td>
                                    <?php
                                    // แยกรายการสินค้าที่ถูกยืมออกเป็นรายการ ๆ
                                    $items = explode(',', $row['itemborrowed']);
                                    foreach ($items as $item) {
                                        $item_parts = explode('(', $item);
                                        $product_name = trim($item_parts[0]);
                                        $quantity = str_replace(')', '', $item_parts[1]);
                                        echo $product_name . ' <span id="B"> (' . $quantity . ') </span> รายการ<br>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo thai_date_time(($row["borrowdatetime"])); ?></td>
                                <td><?php echo thai_date_time(($row["returndate"])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan='6'>ไม่พบข้อมูลในฐานข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- ปุ่มสำหรับสร้างรายงาน PDF -->
        <form id="pdfForm" action="generate_pdf.php" method="GET">
            <?php
            if (isset($_GET["user_id"]) && $_GET["user_id"] != "NULL") : ?>
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($_GET["user_id"]) ?>">
            <?php
            endif;
            if (isset($_GET["start_date"]) && $_GET["start_date"] != "empty" && isset($_GET["end_date"]) && $_GET["end_date"] != "empty") : ?>
                <input type="hidden" name="start_date" id="start_date" value="<?= htmlspecialchars($_GET["start_date"]) ?>">
                <input type="hidden" name="end_date" id="end_date" value="<?= htmlspecialchars($_GET["end_date"]) ?>">
            <?php
            endif; ?>
            <button type="submit">สร้างรายงาน</button>
        </form>
        <!-- <button type="button" class="btn btn-danger" onclick="generatePDF()">สร้างรายงาน PDF</button>
        <button type="button" class="btn btn-danger" onclick="generateFullReport()">สร้างรายงานทั้งหมด</button>
        <button type="button" class="btn btn-danger" onclick="generatePDF()">สร้างรายงาน PDF</button>
        <button type="button" class="btn btn-danger" onclick="generateFullReport()">สร้างรายงานทั้งหมด</button> -->
        <!-- <script>
            function generatePDF() {
                document.getElementById('start_date').value = "<?= htmlspecialchars($_GET['start_date'] ?? '') ?>";
                document.getElementById('end_date').value = "<?= htmlspecialchars($_GET['end_date'] ?? '') ?>";
                document.getElementById('pdfForm').submit();
            }

            function generateFullReport() {
                document.getElementById('start_date').value = "";
                document.getElementById('end_date').value = "";
                document.getElementById('pdfForm').submit();
            }
        </script> -->
    </div>
</body>

</html>