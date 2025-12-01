<?php
function getImagePath($fileOrId) {

    // Lấy ROOT URL đúng của project
    $root = "/Flower_Shop";

    // Nếu rỗng → ảnh mặc định
    if (!$fileOrId) {
        return $root . "/assets/images/no-image.png";
    }

    // Nếu là Google Drive ID
    if (strlen($fileOrId) > 20 && strpos($fileOrId, ".") === false) {
        return $root . "/pages/image_proxy.php?id=" . urlencode($fileOrId);
    }

    // Ảnh local
    return $root . "/assets/images/" . $fileOrId;
}
?>
