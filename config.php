<?php
function getImagePath($fileOrId) {

    // Lấy đường dẫn URL hiện tại (tự động theo nơi đang chạy)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $currentDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\");

    // Nếu đang ở thư mục con (admin/...), phải BACK về root
    $baseUrl = $protocol . "://" . $host;

    // Nếu project chạy trong thư mục con, lấy đường dẫn cha
    $projectRoot = explode("/", trim($currentDir, "/"));
    $projectRoot = "/" . $projectRoot[0];

    // URL root chính xác của project
    $rootUrl = $baseUrl . $projectRoot;

    // Xử lý Google Drive
    if (strlen($fileOrId) > 20 && strpos($fileOrId, '.') === false) {
        $driveUrl = "https://drive.google.com/uc?export=view&id=" . $fileOrId;
        return $rootUrl . "/pages/image_proxy.php?url=" . urlencode($driveUrl);
    }

    // Ảnh local
    return $rootUrl . "/assets/images/" . $fileOrId;
}
?>

