function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const icons = document.querySelectorAll(".icon_password");

    if (field.type === "password") {
        field.type = "text";
        icons.forEach(icon => {
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        });
    } else {
        field.type = "password";
        icons.forEach(icon => {
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        });
    }
}

function togglePassword() {
    togglePasswordVisibility("password");
}

function togglecPassword() {
    togglePasswordVisibility("confirm_password");
}

// เลือกปุ่มปิด Modal
var closeModalButton = document.getElementById('close');
// เลือกพื้นหลังของ Modal
var modalAlertbook = document.querySelector('.edit_profile_status');

// เมื่อคลิกที่ปุ่มปิดหรือที่พื้นหลังของ Modal
closeModalButton.addEventListener('click', function () {
    closeModal();
});

modalAlertbook.addEventListener('click', function (event) {
    if (event.target === modalAlertbook) {
        closeModal();
    }
});

// ฟังก์ชันในการปิด Modal
function closeModal() {
    var modal = document.querySelector('.edit_profile_status');
    modal.style.display = 'none';
    // เคลียร์การตั้งค่าเวลาในการปิด Modal หลังจาก 3 วินาที
    clearTimeout(closeModalTimer);
}

// กำหนดเวลาในการปิด Modal หลังจาก 3 วินาที
var closeModalTimer = setTimeout(function () {
    closeModal();
}, 3000);
