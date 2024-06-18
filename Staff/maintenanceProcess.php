<?php
session_start();
require_once '../assets/database/dbConfig.php';
date_default_timezone_set('Asia/Bangkok');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // รับค่าจากฟอร์ม
    $selectedIds = $_POST['selected_ids'];
    $start_maintenance = $_POST['start_maintenance'];
    $end_maintenance = $_POST['end_maintenance'];
    $name_staff = $_POST['name_staff'];
    $note = $_POST['note'] ?? ''; // ตรวจสอบว่ามี note หรือไม่ ถ้าไม่มีกำหนดเป็นค่าว่าง

    $sMessage = "แจ้งเตือนการบำรุงรักษา\n";

    // เริ่มการทำงานในแต่ละ ID ที่เลือก
    foreach ($selectedIds as $id) {
        // อัพเดทสถานะของอุปกรณ์
        $update_query = $conn->prepare("UPDATE crud SET availability = 1 WHERE id = :id");
        $update_query->bindParam(':id', $id, PDO::PARAM_INT);
        $update_query->execute();

        // ดึงข้อมูลผู้ใช้
        if (isset($_SESSION['staff_login'])) {
            $user_id = $_SESSION['staff_login'];
            $stmt = $conn->prepare("SELECT * FROM users_db WHERE user_ID = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $staff_id = $_SESSION['staff_login'];
        $user_query = $conn->prepare("SELECT user_ID, pre, firstname, lastname FROM users_db WHERE user_ID = :staff_id");
        $user_query->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
        $user_query->execute();
        $users_LOG = $user_query->fetch(PDO::FETCH_ASSOC);
        $authID = $users_LOG['user_ID'];
        $log_Name = $users_LOG['pre'] . $users_LOG['firstname'] . ' ' . $users_LOG['lastname'];
        $log_Status = 'เริ่มต้นกาบำรุงรักษา';

        // ดึงข้อมูลอุปกรณ์
        $stmt = $conn->prepare("SELECT serial_number, sci_name, categories FROM crud WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        $serial_number = $item['serial_number'];
        $sci_name = $item['sci_name'];
        $categories = $item['categories'];

        // เพิ่มข้อมูลการบำรุงรักษาเข้าสู่ฐานข้อมูล
        $insert_query_01 = $conn->prepare("INSERT INTO logs_maintenance (serial_number, sci_name, categories, start_maintenance, end_maintenance, name_staff, note) VALUES (:serial_number, :sci_name, :categories, :start_maintenance, :end_maintenance, :name_staff, :note)");
        $insert_query_01->bindParam(':serial_number', $serial_number, PDO::PARAM_STR);
        $insert_query_01->bindParam(':sci_name', $sci_name, PDO::PARAM_STR);
        $insert_query_01->bindParam(':categories', $categories, PDO::PARAM_STR);
        $insert_query_01->bindParam(':start_maintenance', $start_maintenance, PDO::PARAM_STR);
        $insert_query_01->bindParam(':end_maintenance', $end_maintenance, PDO::PARAM_STR);
        $insert_query_01->bindParam(':name_staff', $name_staff, PDO::PARAM_STR);
        $insert_query_01->bindParam(':note', $note, PDO::PARAM_STR);
        $result_01 = $insert_query_01->execute();

        // เพิ่มข้อมูลการบำรุงรักษาเข้าสู่ฐานข้อมูล
        $insert_query_02 = $conn->prepare("INSERT INTO logs_maintenance_2 (authID, log_Name, log_Date, log_Status) VALUES (:authID, :log_name, NOW(), :log_status)");
        $insert_query_02->bindParam(':authID', $authID, PDO::PARAM_STR);
        $insert_query_02->bindParam(':log_name', $log_Name, PDO::PARAM_STR);
        $insert_query_02->bindParam(':log_status', $log_Status, PDO::PARAM_STR);
        $result_02 = $insert_query_02->execute();

        if ($result_01 && $result_02) {
            $_SESSION['maintenanceSuccess'] = "เริ่มต้นกระบวนการการบำรุงรักษา";
        } else {
            $_SESSION['error'] = "Data has not been updated successfully";
        }

        header("Location: /maintenance");
        exit;

        // เพิ่มรายละเอียดในข้อความ
        if ($item) {
            $sMessage .= "รายการ: " . $item['sci_name'] . "\n";
            $sMessage .= "ประเภท: " . $item['categories'] . "\n";
        }
    }

    // สรุปข้อความ
    $sMessage .= "วันที่บำรุงรักษา : " . date('d/m/Y') . "\n";
    $sMessage .= "บำรุงรักษาสำเร็จ : " . date('d/m/Y', strtotime($end_maintenance)) . "\n";
    $sMessage .= "หมายเหตุ: " . $note . "\n";
    $sMessage .= "-------------------------------";

    $sToken = "7ijLerwP9wvrN0e3ykl8y3y9c991p1WQuX1Dy8Pv3Fx";

    // ตั้งค่า Line Notify
    $chOne = curl_init();
    curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
    curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($chOne, CURLOPT_POST, 1);
    curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . urlencode($sMessage));
    $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken);
    curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($chOne);

    if (curl_error($chOne)) {
        echo 'error:' . curl_error($chOne);
    } else {
        $result_ = json_decode($result, true);
        echo "<script>
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'การยืมเสร็จสิ้น',
                showConfirmButton: false,
                timer: 1500
            }).then(function() {
                window.location.href = 'home.php';
            });
            </script>";
    }
    curl_close($chOne);
    header('Location: /maintenance');
    exit;
}