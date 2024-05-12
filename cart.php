<?php
session_start();
include_once 'assets/database/connect.php';

// Check if cart session exists, create one if not
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle actions like add, clear, or remove items from the cart
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'add' && isset($_GET['item'])) {
        $itemToAdd = $_GET['item'];
        $_SESSION['cart'][] = $itemToAdd;
        header('Location: cart.php');
        exit;
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: cart.php');
        exit;
    } elseif ($action === 'remove' && isset($_GET['item'])) {
        $itemToRemove = $_GET['item'];
        $key = array_search($itemToRemove, $_SESSION['cart']);
        if ($key !== false) {
            unset($_SESSION['cart'][$key]);
        }
        header('Location: cart.php');
        exit;
    }
}

// Check if the user is logged in
if (!isset($_SESSION['user_login']) && !isset($_SESSION['staff_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('Location: auth/sign_in.php');
    exit;
}

// Fetch user data if logged in
if (isset($_SESSION['user_login'])) {
    $user_id = $_SESSION['user_login'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (isset($_SESSION['staff_login'])) {
    $user_id = $_SESSION['staff_login'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกรายการวัสดุ อุปกรณ์ และเครื่องมือ</title>
    <!-- ส่วนของ Link -->
    <link href="assets/logo/LOGO.jpg" rel="shortcut icon" type="image/x-icon" />
    <link rel="stylesheet" href="assets/font-awesome/css/all.css">
    <link rel="stylesheet" href="assets/css/navigator.css">
    <link rel="stylesheet" href="assets/css/cart.css">
</head>

<body>
    <?php include('includes/header.php'); ?>

    <?php if (empty($_SESSION['cart'])) : ?>
        <div class="main_cart">
            <div class="main_cart_content_non_select">
                <div class="non_select_1">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span id="B">ไม่มีวัสดุ อุปกรณ์และเครื่องมือถูกเลือกอยู่</span>
                </div>
                <div class="non_select_2">
                    <a href="../project/"><span>กลับหน้าหลัก</span></a>
                    <span class="warning">!! ถ้าต้องการเลือกวัสดุ อุปกรณ์และเครื่องมือเพิ่มให้กลับหน้าหลัก !!</span>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="main_cart_content">
            <form method="post" action="waiting_for_approval.php">
                <div class="table_section">
                    <div class="count_list">
                        <div class="count_list_1">
                            <span>รายการที่เลือกทั้งหมด </span>
                            <?php echo count($_SESSION['cart']); ?><span> รายการ</span>
                        </div>
                        <div class="count_list_2">
                            <a href="booking_log.php">ตรวจสอบการจอง</a>
                        </div>
                    </div>
                    <table class="cart_data">
                        <tr>
                            <th class="th_img"></th>
                            <th class="th_name"><span id="B">ชื่อรายการ</span></th>
                            <th class="th_categories"><span id="B">ประเภท</span></th>
                            <th class="th_amount"><span id="B">จำนวน</span></th>
                        </tr>
                        <?php foreach ($_SESSION['cart'] as $item) : ?>
                            <?php
                            // Retrieve product details from the database based on the item
                            $query = $conn->prepare("SELECT * FROM crud WHERE img = :item");
                            $query->bindParam(':item', $item, PDO::PARAM_STR);
                            $query->execute();
                            $product = $query->fetch(PDO::FETCH_ASSOC);

                            // Check if the product is found
                            if ($product) {
                                $categories = $product['categories'];
                                $productName = $product['sci_name'];
                                $imageURL = 'assets/uploads/' . $product['img'];
                            ?>
                                <tr>
                                    <td><img src="<?php echo $imageURL; ?>" alt="<?php echo $productName; ?>"></td>
                                    <td class="product-name"><?php echo $productName; ?></td>
                                    <td class="product-name">
                                        <span><?php echo $categories ?></span>
                                    </td>
                                    <td>
                                        <div class="amount_delete">
                                            <input type="number" name="amount[<?php echo $item; ?>]" value="1" min="1">
                                            <a class="btn_delete" href="cart.php?action=remove&item=<?php echo $item; ?>">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php endforeach; ?>
                    </table>
                </div>
                <div class="footer_section">
                    <div class="footer_section_return_date">
                        <span>ระบุวันที่ เวลาที่คืนอุปกรณ์ และเครื่องมือ</span>
                        <input type="datetime-local" name="return_date" required>
                    </div>
                    <div class="footer_section_btn">
                        <div class="footer_section_btn_1">
                            <a href="../project/" class="back_to_main">กลับหน้าหลัก</a>
                        </div>
                        <div class="footer_section_btn_2">
                            <button class="submit" type="submit" name="update">ยืนยัน</button>
                            <a href="cart.php?action=clear" class="clear_cart">ยกเลิกสิ่งที่เลือกทั้งหมด</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
    <script src="assets/js/ajax.js"></script>
</body>

</html>