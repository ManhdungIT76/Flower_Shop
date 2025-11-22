<?php
if (!isset($_GET['url'])) {
    http_response_code(400);
    exit("Missing URL");
}

$url = $_GET['url'];
$context = stream_context_create([
    "http" => [
        "header" => "User-Agent: Mozilla/5.0\r\n"
    ]
]);

$image = @file_get_contents($url, false, $context);

if ($image === false) {
    http_response_code(404);
    exit("Không tải được ảnh");
}

header("Content-Type: image/jpeg");
echo $image;
