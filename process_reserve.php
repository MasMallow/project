<html>

<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>

<body>
<?php
session_start();
include_once 'assets/database/connect.php';

if (!isset($_SESSION['user_login']) && !isset($_SESSION['admin_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('Location: auth/sign_in.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $reservationDate = $_POST['reservation_date'];
        $items = $_POST['amount'];
        $reservationDates = $_POST['reservation_date'];

        if (isset($_SESSION['user_login'])) {
            $user_id = $_SESSION['user_login'];
        } elseif (isset($_SESSION['admin_login'])) {
            $user_id = $_SESSION['admin_login'];
        }

        $user_query = $conn->prepare("SELECT firstname FROM users WHERE user_id = :user_id");
        $user_query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_query->execute();
        $user = $user_query->fetch(PDO::FETCH_ASSOC);
        $firstname = $user['firstname']; // User's first name

        $sMessage = "รายการจองวัสดุอุปกรณ์และเครื่องมือ\n";
        $sMessage .= "ชื่อผู้จอง : " . $firstname . "\n";

        foreach ($_SESSION['reserve_cart'] as $item) {
            // Retrieve product details from the database based on the item
            $query = $conn->prepare("SELECT * FROM crud WHERE file_name = :item");
            $query->bindParam(':item', $item, PDO::PARAM_STR);
            $query->execute();
            $product = $query->fetch(PDO::FETCH_ASSOC);
            $productName = $product['product_name'];

            // Retrieve the quantity of the item
            $quantity = isset($items[$item]) ? $items[$item] : 0;

            // Append product details to sMessage
            $sMessage .= "ชื่ออุปกรณ์ : " . $productName . ", จำนวน : " . $quantity . " ชิ้น\n";
        }

        // Get the first and last return dates for the message
        $firstReturnDate = date('Y-m-d', strtotime($reservationDates));
        $lastReturnDate = date('Y-m-d', strtotime($reservationDates));

        // Insert borrow history into the database
        foreach ($_SESSION['reserve_cart'] as $item) {
            $productName = '';

            // Retrieve product details from the database based on the item
            $query = $conn->prepare("SELECT product_name FROM crud WHERE file_name = :item");
            $query->bindParam(':item', $item, PDO::PARAM_STR);
            $query->execute();
            $product = $query->fetch(PDO::FETCH_ASSOC);
            $productName = $product['product_name'];

            // Retrieve the quantity of the item
            $quantity = isset($items[$item]) ? $items[$item] : 0;

            // Insert the borrow history into the database
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, firstname, product_name, quantity, reservation_date, created_at) VALUES (:user_id, :firstname, :product_name, :quantity, :reservation_date, NOW())");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
            $stmt->bindParam(':product_name', $productName, PDO::PARAM_STR);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':reservation_date', $reservationDate, PDO::PARAM_STR);
            $stmt->execute();
        }
        $sMessage .= "วันที่ขอยืม : " . date('Y-m-d') . "\n"; // Date of borrowing
        $sMessage .= "วันที่นำมาคืน : " . $firstReturnDate . "\n"; // Return dates range
        $sMessage .= "-------------------------------";

        $_SESSION['reserve_cart'] = [];

        $sToken = "7ijLerwP9wvrN0e3ykl8y3y9c991p1WQuX1Dy8Pv3Fx";

        // Line Notify settings
        $chOne = curl_init();
        curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($chOne, CURLOPT_POST, 1);
        curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . $sMessage);
        $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken . '');
        curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($chOne);

        //Result error 
        if (curl_error($chOne)) {
            echo 'error:' . curl_error($chOne);
        } else {
            $result_ = json_decode($result, true);
            echo "<script>
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: 'การจองเสร็จสิ้น',
            showConfirmButton: false,
            timer: 1500
        }).then(function() {
            window.location.href = 'index.php';
        });
    </script>";
        }
        curl_close($chOne);
        exit;
    }
}
