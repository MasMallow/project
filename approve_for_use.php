<?php
session_start();
include_once 'assets/database/connect.php';

// ตรวจสอบว่าพนักงานเข้าสู่ระบบหรือไม่
if (!isset($_SESSION['staff_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('Location: auth/sign_in.php');
    exit;
}

// ดึงข้อมูลผู้ใช้หากเข้าสู่ระบบ
if (isset($_SESSION['user_login']) || isset($_SESSION['staff_login'])) {
    // ตั้งค่า user_id ตาม session ที่มี
    $user_id = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : $_SESSION['staff_login'];

    // เตรียมคำสั่ง SQL เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // ดึงข้อมูลผู้ใช้
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}
// ฟังก์ชันแปลงวันที่และเวลาเป็นรูปแบบภาษาไทย
function thai_date_time($datetime)
{
    $thai_month_arr = array(
        "0" => "",
        "1" => "ม.ค.",
        "2" => "ก.พ.",
        "3" => "มี.ค.",
        "4" => "เม.ย.",
        "5" => "พ.ค.",
        "6" => "มิ.ย.",
        "7" => "ก.ค.",
        "8" => "ส.ค.",
        "9" => "ก.ย.",
        "10" => "ต.ค.",
        "11" => "พ.ย.",
        "12" => "ธ.ค."
    );

    $day = date("w", strtotime($datetime)); // วันในสัปดาห์ (0-6)
    $date = date("j", strtotime($datetime)); // วันที่
    $month = date("n", strtotime($datetime)); // เดือน (1-12)
    $year = date("Y", strtotime($datetime)) + 543; // ปี พ.ศ.
    $time = date("H:i น.", strtotime($datetime)); // เวลา

    return "วัน" . "ที่ " . $date . " " . $thai_month_arr[$month] . " พ.ศ." . $year . " <br> เวลา " . $time;
}
$stmt = $conn->prepare("SELECT * FROM waiting_for_approval WHERE approvaldatetime IS NULL AND approver IS NULL AND situation IS NULL ORDER BY sn");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$num = count($data); // นับจำนวนรายการ
$previousSn = '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติการขอใช้</title>

    <link rel="stylesheet" href="assets/font-awesome/css/all.css">
    <link rel="stylesheet" href="assets/css/navigator.css">
    <link rel="stylesheet" href="assets/css/approval.css">
</head>

<body>
    <?php include('includes/header.php') ?>
    <div class="header_approve">
        <div class="header_approve_section">
            <a href="../project/"><i class="fa-solid fa-arrow-left-long"></i></a>
            <span id="B">อนุมัติการขอใช้</span>
        </div>
    </div>
    <div class="approve_section">
        <div class="approve_table_section">
            <?php if (empty($data)) { ?>
                <div class="approve_not_found_section">
                    <span id="B">ไม่พบข้อมูลการขอใช้</span>
                </div>
            <?php } ?>
            <?php if (!empty($data)) { ?>
                <table class="approve_table_data">
                    <div class="approve_table_header">
                        <span>รายการที่ขอใช้งานทั้งหมด <span id="B"><?php echo $num; ?></span> รายการ</span>
                    </div>
                    <thead>
                        <tr>
                            <th class="s_number"><span id="B">หมายเลขรายการ</span></th>
                            <th class="name_use"><span id="B">ชื่อ - นามสกุล</span></th>
                            <th class="item_name"><span id="B">รายการที่ขอใช้งาน</span></th>
                            <th class="borrow_booking"><span id="B">วันเวลาที่ขอใช้งาน</span></th>
                            <th class="return"><span id="B">วันเวลาที่สิ้นสุดขอใช้งาน</span></th>
                            <th class="approval"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($data as $row) :
                            if ($previousSn != $row['sn']) { ?>
                                <tr>
                                    <td class="sn"><?php echo $row['sn']; ?></td>
                                    <td><?php echo $row['firstname']; ?></td>
                                    <td>
                                        <?php
                                        // แยกข้อมูล Item Borrowed
                                        $items = explode(',', $row['itemborrowed']);

                                        // แสดงข้อมูลรายการที่ยืม
                                        foreach ($items as $item) {
                                            $item_parts = explode('(', $item); // แยกชื่อสินค้าและจำนวนชิ้น
                                            $product_name = trim($item_parts[0]); // ชื่อสินค้า (ตัดวงเล็บออก)
                                            $quantity = str_replace(')', '', $item_parts[1]); // จำนวนชิ้น (ตัดวงเล็บออกและตัดช่องว่างข้างหน้าและหลัง)
                                            echo $product_name . ' <span id="B"> ' . $quantity . ' </span> รายการ<br>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo thai_date_time($row['borrowdatetime']); ?></td>
                                    <td><?php echo thai_date_time($row['returndate']); ?></td>
                                    <td>
                                        <form class="approve_form" method="POST" action="process_return">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="udi" value="<?php echo $row['udi']; ?>">
                                            <button class="confirm_approve" type="submit" name="confirm"><i class="fa-solid fa-circle-check"></i></button>
                                            <button class="cancel_approve" type="submit" name="cancel"><i class="fa-solid fa-circle-xmark"></i></button>
                                        </form>
                                    </td>
                                </tr>
                        <?php
                                $previousSn = $row['sn'];
                            }
                        endforeach;
                        ?>
                    </tbody>
                </table>
        </div> <?php } ?>

    </div>
</body>

</html>