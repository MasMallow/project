<?php
include_once '../assets/database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['sci_name'], $_POST['quantity'], $_POST['product_type'])) {
        $id = $_POST['id'];
        $sci_name = $_POST['sci_name'];
        $quantity = $_POST['quantity'];
        $productType = $_POST['product_type']; // Ensure this matches the form input name
        $fileName = basename($_FILES["file"]["name"]);

        // Check if a new image is uploaded
        if (isset($_FILES['file']["name"])) {
            $targetDir = "../uploads/"; // เปลี่ยนเป็นชื่อโฟลเดอร์ที่ต้องการ
            $fileName = basename($_FILES["file"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf');
            if (!in_array($fileType, $allowTypes)) {
                echo "Sorry, only JPG, JPEG, PNG, GIF, and PDF files are allowed.";
                exit();
            }
            if (in_array($fileType, $allowTypes)) {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                    // Update product information in the database with the new image
                    $stmt = $conn->prepare("UPDATE crud SET sci_name = ?, amount = ?, Type = ?, img = ? WHERE user_id = ?");
                    $stmt->bind_param('sissi', $sci_name, $quantity, $productType, $fileName, $id);
                    $stmt->execute();

                    header("Location: add-remove-update.php");
                    exit();
                } else {
                    echo "Failed to move the uploaded file.";
                }
            } else {
                echo "Invalid file type. Please upload an image.";
            }
        } else {
            // No new image uploaded; update product information without changing the image
            $stmt = $conn->prepare("UPDATE crud SET sci_name = ?, amount = ?, Type = ? WHERE user_id = ?");
            $stmt->bind_param('sisi', $sci_name, $quantity, $productType, $id);
            $stmt->execute();

            header("Location: add-remove-update.php");
            exit();
        }
    } else {
        echo "Invalid data received.";
    }
} else {
    echo "Invalid request method.";
}
