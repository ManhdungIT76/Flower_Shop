<?php

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit("Missing image id");
}

// sanitize để tránh injection
$image_id = preg_replace("/[^A-Za-z0-9_-]/", "", $_GET['id']);

// Thư mục cache
$cacheDir = __DIR__ . "/../assets/images_cache";

// Tạo thư mục nếu chưa tồn tại
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

// Tên file cache KHÔNG có đuôi (để tí nữa tự thêm dựa theo MIME)
$cacheFileBase = $cacheDir . "/" . $image_id;

// Kiểm tra file cache với mọi loại đuôi
$possibleExt = ['jpg', 'jpeg', 'png', 'webp'];
foreach ($possibleExt as $ext) {
    $full = $cacheFileBase . "." . $ext;
    if (file_exists($full)) {
        header("Content-Type: " . mime_content_type($full));
        readfile($full);
        exit;
    }
}

// Nếu không có cache → tải từ Google Drive
$driveUrl = "https://drive.google.com/uc?export=download&id=$image_id";

$context = stream_context_create([
    "http" => [
        "header" => "User-Agent: Mozilla/5.0\r\n"
    ]
]);

$image = @file_get_contents($driveUrl, false, $context);

if (!$image) {
    http_response_code(404);
    exit("Không tải được ảnh từ Google Drive");
}

// Detect MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_buffer($finfo, $image);
finfo_close($finfo);

// Tự động chọn đuôi file phù hợp MIME
$ext = match ($mime) {
    "image/png"  => ".png",
    "image/webp" => ".webp",
    default      => ".jpg"
};

// Lưu file cache
$cacheFile = $cacheFileBase . $ext;
file_put_contents($cacheFile, $image);

// Trả ảnh về cho trình duyệt
header("Content-Type: $mime");
echo $image;
?>
