<?php
session_start();
require_once 'assets/database/dbConfig.php';
include_once 'assets/includes/thai_date_time.php';
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ และดึงข้อมูลผู้ใช้
if (isset($_SESSION['staff_login'])) {
    $userID = $_SESSION['staff_login'];
    $stmt = $conn->prepare("SELECT * FROM users_db WHERE userID = :userID");
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('Location: auth/sign_in.php');
    exit;
}

// กำหนดค่าเริ่มต้น
$searchTitle = "";
$searchValue = "";
if (isset($_GET['search'])) {
    $searchTitle = "ค้นหา \"" . htmlspecialchars($_GET['search']) . "\" | ";
    $searchValue = htmlspecialchars($_GET['search']);
}

// ฟังก์ชันในการดึงข้อมูลผู้ใช้ตามเงื่อนไข
try {
    $stmt = $conn->prepare("SELECT * FROM users_db WHERE status = 'wait_approved' ");
    $stmt->execute();
    $num = $stmt->rowCount();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// ฟังก์ชันในการดึงข้อมูลผู้ใช้ตามเงื่อนไข
function fetchUsers($conn, $status, $role, $search = null)
{
    if ($search) {
        $search = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT * FROM users_db WHERE (userID LIKE :search OR pre LIKE :search OR firstname LIKE :search OR lastname LIKE :search) AND status = :status AND urole = :role");
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users_db WHERE status = :status AND urole = :role");
    }

    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ตั้งค่าตำแหน่งและสถานะตามการจัดการ
$role = 'user';
$search = isset($_GET["search"]) ? $_GET["search"] : null;
if ($request_uri === '/manage_users/management_user') {
    $users_approved = fetchUsers($conn, 'approved', $role, $search);
} elseif ($request_uri === '/manage_users/undisapprove_user') {
    $users_banned = fetchUsers($conn, 'not_approved', $role, $search);
}

// การจัดการคำขอ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_POST['userID'];

    // อนุมัติผู้ใช้
    if (isset($_POST['approval_user'])) {
        $staff_id = $_SESSION['staff_login'];
        $stmt = $conn->prepare("SELECT * FROM users_db WHERE userID = :userID");
        $stmt->bindParam(':userID', $staff_id, PDO::PARAM_INT);
        $stmt->execute();
        $staff_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $approver = $staff_data['pre'] . $staff_data['firstname'] . ' ' . $staff_data['lastname'];
        date_default_timezone_set('Asia/Bangkok');
        $approvalDateTime = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("UPDATE users_db SET status = 'approved', approved_by = :approved_by, approved_date = :approved_date WHERE userID = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->bindParam(':approved_by', $approver, PDO::PARAM_STR);
        $stmt->bindParam(':approved_date', $approvalDateTime, PDO::PARAM_STR);
        $stmt->execute();

        // รีเฟรชหน้าเว็บ
        header('Location: /manage_users/undisapprove_user');
        exit;
    }
    // ระงับผู้ใช้
    elseif (isset($_POST['ban_user'])) {
        $stmt = $conn->prepare("UPDATE users_db SET status = 'not_approved' WHERE userID = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();

        // รีเฟรชหน้าเว็บ
        header('Location: /manage_users/management_user');
        exit;
    }
    // ลบผู้ใช้
    elseif (isset($_POST['delete_user'])) {
        $stmt = $conn->prepare("DELETE FROM users_db WHERE userID = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();

        // รีเฟรชหน้าเว็บ
        header('Location: /manage_users');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจัดการบัญชีผู้ใช้</title>
    <link href="<?php echo $base_url; ?>/assets/logo/LOGO.jpg" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/font-awesome/css/all.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/navigator.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/manage_users.css">
</head>

<body>
    <header>
        <?php include 'assets/includes/navigator.php'; ?>
    </header>
    <div class="manage_user">
        <div class="header_user_manage_section">
            <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left-long"></i></a>
            <span id="B">การจัดการบัญชีผู้ใช้</span>
        </div>
        <div class="user_manage_btn_section">
            <div class="user_manage_btn">
                <a href="/manage_users" class="<?= ($request_uri == '/manage_users') ? 'active' : ''; ?> btn_user_manage_01">อมุมัติบัญชีผู้ใช้</a>
                <a href="/manage_users/management_user" class="<?= ($request_uri == '/manage_users/management_user') ? 'active' : ''; ?> btn_user_manage_02">ตรวจสอบและแก้ไขบัญชี</a>
                <a href="/manage_users/undisapprove_user" class="<?= ($request_uri == '/manage_users/undisapprove_user') ? 'active' : ''; ?> btn_user_manage_03">ยกเลิกระงับบัญชี</a>
            </div>
            <!-- แบบฟอร์มการค้นหา -->
            <form class="user_manage_search" method="get">
                <input type="hidden" name="manage" value="<?= htmlspecialchars($manage); ?>">
                <input class="search" type="search" name="search" value="<?= htmlspecialchars($searchValue); ?>" placeholder="ค้นหา">
                <button class="search" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
        <?php if (in_array($request_uri, ['/manage_users', '/manage_users/management_user', '/manage_users/undisapprove_user'])) : ?>
            <?php
            $header = '';
            $user_list = [];

            switch ($request_uri) {
                case '/manage_users':
                    $header = 'บัญชีที่รออนุมัติ';
                    $user_list = $users;
                    break;
                case '/manage_users/management_user':
                    $header = 'บัญชีที่อนุมัติแล้ว';
                    $user_list = $users_approved;
                    break;
                case '/manage_users/undisapprove_user':
                    $header = 'บัญชีที่ถูกระงับ';
                    $user_list = $users_banned;
                    break;
            }
            ?>
            <?php if (!empty($user_list)) : ?>
                <div class="manage_user_table_section">
                    <div class="user_manage_data_header">
                        <span>จำนวนบัญชีทั้งหมด <span id="B">(<?= count($user_list); ?>)</span> บัญชี</span>
                    </div>
                    <table class="user_manage_data">
                        <thead>
                            <tr>
                                <th class="UID"><span id="B">UID</span></th>
                                <th class="name"><span id="B">ชื่อ - นามสกุล</span></th>
                                <th class="role"><span id="B">ตำแหน่ง</span></th>
                                <th class="agency"><span id="B">สังกัด</span></th>
                                <th class="phone_number"><span id="B">เบอร์โทรศัพท์</span></th>
                                <th class="created_at"><span id="B">สมัครบัญชีเมื่อ</span></th>
                                <th class="urole"><span id="B">ประเภท</span></th>
                                <th class="status"><span id="B">สถานะ</span></th>
                                <th class="operation"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_list as $user) : ?>
                                <tr>
                                    <td class="UID"><?= $user['userID']; ?></td>
                                    <td><?= $user['pre'] . $user['firstname'] . " " . $user['lastname']; ?></td>
                                    <td><?= $user['role']; ?></td>
                                    <td><?= $user['agency']; ?></td>
                                    <td><?= format_phone_number($user['phone_number']); ?></td>
                                    <td><?= thai_date_time($user['created_at']); ?></td>
                                    <td><?= $user['urole'] === 'user' ? 'ผู้ใช้งานทั่วไป' : 'อื่น ๆ'; ?></td>
                                    <td class="<?= $user['status'] === 'approved' ? 'green_text' : 'red_text'; ?>"><?= $user['status'] === 'approved' ? 'อนุมัติแล้ว' : 'ไม่ได้รับอนุมัติ'; ?></td>
                                    <td class="operation">
                                        <form method="post">
                                            <div class="btn_user_manage_section">
                                                <input type="hidden" name="userID" value="<?= $user['userID']; ?>">
                                                <?php if ($request_uri == '/manage_users') : ?>
                                                    <button type="submit" class="approval_user" name="approval_user">
                                                        <i class="fa-regular fa-circle-check"></i>
                                                    </button>
                                                <?php elseif ($request_uri == '/manage_users/management_user') : ?>
                                                    <button class="edit_user" type="submit" name="edit_user"><i class="fa-solid fa-pencil"></i></button>
                                                    <button class="ban_user" type="submit" name="ban_user"><i class="fa-solid fa-user-slash"></i></button>
                                                    <button class="delete_user" type="submit" name="delete_user"><i class="fa-solid fa-trash-can"></i></button>
                                                <?php elseif ($request_uri == '/manage_users/undisapprove_user') : ?>
                                                    <button type="submit" class="approval_user" name="approval_user">
                                                        <i class="fa-regular fa-circle-check"></i>
                                                    </button>
                                                    <button class="delete_user" type="submit" name="delete_user"><i class="fa-solid fa-trash-can"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="user_manage_not_found">
                    <i class="fa-solid fa-user-xmark"></i>
                    <span id="B">ไม่มีพบบัญชีผู้ใช้ในระบบ</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <!-- JavaScript -->
    <script src="assets/js/ajax.js"></script>
</body>

</html>