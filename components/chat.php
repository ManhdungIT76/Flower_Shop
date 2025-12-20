<?php
session_start();

header("Content-Type: application/json; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ================== INPUT ==================
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($data["message"] ?? "");

if ($userMessage === "") {
    echo json_encode(["error" => "Không nhận được tin nhắn từ client"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== LOGIN / GUEST ID ==================
// Nếu chưa đăng nhập -> vẫn lưu lịch sử theo session (guest)
$userId = "";
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $userId = (string)$_SESSION['user']['id']; // VD: "KH003"
} else {
    $userId = "GUEST_" . session_id();
}

// ================== HELPERS ==================
function isGreetingOnly($text) {
    $t = mb_strtolower(trim($text));
    $t = preg_replace('/[^\p{L}\p{N}\s]/u', '', $t);

    $greetings = [
        'chào', 'chào shop', 'chào bạn', 'hello', 'hi', 'hey',
        'xin chào', 'alo', 'ad ơi', 'shop ơi'
    ];

    foreach ($greetings as $g) {
        if ($t === $g) return true;
    }

    if (mb_strlen($t) <= 10 && !preg_match('/hoa|giá|mua|tặng|bó|giỏ/u', $t)) {
        return true;
    }
    return false;
}

function moneyToInt($s) {
    $s = mb_strtolower(trim($s));
    $s = str_replace([',', '.', 'đ', 'vnđ', 'vnd', ' '], '', $s);

    // 100k, 200k
    if (function_exists('str_ends_with') && str_ends_with($s, 'k')) return (float)rtrim($s,'k') * 1000;
    if (!function_exists('str_ends_with') && substr($s, -1) === 'k') return (float)rtrim($s,'k') * 1000;

    // 100000
    return (float)preg_replace('/[^\d]/', '', $s);
}

function parsePriceRange($text) {
    $t = mb_strtolower($text);
    $min = null; $max = null;

    // "từ 400k đến 500k"
    if (preg_match('/từ\s*([\d\., ]+k?)\s*(đ|vnd|vnđ)?\s*đến\s*([\d\., ]+k?)/iu', $t, $m)) {
        $min = moneyToInt($m[1]);
        $max = moneyToInt($m[3]);
        return [$min, $max];
    }

    // "400k-450k" (dấu - hoặc –)
    if (preg_match('/\b(\d{1,3})\s*k\s*[-–]\s*(\d{1,3})\s*k\b/iu', $t, $m)) {
        $min = (float)$m[1] * 1000;
        $max = (float)$m[2] * 1000;
        return [$min, $max];
    }

    // "dưới 200k"
    if (preg_match('/(dưới|<=|<)\s*([\d\., ]+k?)/iu', $t, $m)) {
        $max = moneyToInt($m[2]);
        return [null, $max];
    }

    // "trên 200k"
    if (preg_match('/(trên|>=|>)\s*([\d\., ]+k?)/iu', $t, $m)) {
        $min = moneyToInt($m[2]);
        return [$min, null];
    }

    // BONUS: bắt số đứng 1 mình "100000"
    if (preg_match('/\b(\d{4,})\b/u', $t, $m)) {
        if (mb_strpos($t, 'dưới') !== false) return [null, (float)$m[1]];
    }

    return [null, null];
}

// token số là giá -> không đem đi LIKE product_name
function isPriceLikeToken($tk) {
    $tk = mb_strtolower(trim($tk));
    if (preg_match('/^\d+$/u', $tk)) return true;     // 100000
    if (preg_match('/^\d+k$/u', $tk)) return true;    // 100k
    return false;
}

// từ đệm / vô nghĩa -> tránh siết query vào product_name
function isMeaninglessToken($tk) {
  $tk = mb_strtolower(trim($tk));
  $generic = [
    'rồi','ok','oke','ờ','à','ạ','nha','nhé','đi',
    'giùm','giúp','cho','tôi','mình','em','anh','chị','bạn','shop','ad',
    'tìm','mua','chọn','gợi ý','cần','muốn',
    'sản','phẩm','sản phẩm','mặt hàng','item','sp',
    'hoa','bó','giỏ','lẵng','kệ','chậu',
    'dưới','trên','từ','đến','tầm','khoảng','giá'
  ];
  return in_array($tk, $generic, true);
}

function extractTokens($text) {
    $t = mb_strtolower($text);
    $t = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $t);

    // stopwords mạnh (bạn có thể bổ sung thêm)
    $stop = [

    // ===== đại từ / xưng hô =====
    'tôi','mình','em','anh','chị','bạn','shop','ad','admin','chủ shop',

    // ===== động từ chung =====
    'tìm','tìm kiếm','kiếm','xem','coi','chọn','mua','đặt','order','lấy',
    'giúp','giúp tôi','giúp mình','hỗ trợ','tư vấn','cho','cho tôi','cho mình',

    // ===== lượng từ / định lượng =====
    'một','vài','mấy','nhiều','ít','tất cả','toàn bộ','bất kỳ',
    'khoảng','tầm','tầm khoảng','tầm giá',

    // ===== câu hỏi / tình thái =====
    'không','không ạ','không nhỉ','được không','được ko','ko','k','hok',
    'nhỉ','ạ','ơi','vậy','thế','nào','gì','sao','không biết',

    // ===== danh từ chung gây nhiễu =====
    'sản','phẩm','sản phẩm','mặt hàng','item','items','sp','hàng',

    // ===== liên từ / giới từ =====
    'với','và','hay','hoặc','là','thì','mà',

    // ===== giá cả (đã parse riêng) =====
    'giá','giá cả','bao nhiêu','tiền','đồng','vnđ','vnd','đ',
    'rẻ','rẻ nhất','cao','thấp',

    // ===== phạm vi =====
    'dưới','trên','từ','đến','<=','>=','<','>',

    // ===== hình thức =====
    'loại','mẫu','kiểu','dạng','size','form','phong cách',

    // ===== hoa chung (để KHÔNG siết tên sản phẩm) =====
    'hoa','bó','giỏ','lẵng','kệ','chậu','cây',

    // ===== xã giao / lịch sự =====
    'vui lòng','làm ơn','nhé','giùm','giúp với',

    // ===== khác =====
    'còn','nữa','thêm','gợi ý','đề xuất','recommend'
];

    foreach ($stop as $w) {
        $t = preg_replace('/\b'.preg_quote($w,'/').'\b/u', ' ', $t);
    }

    $t = trim(preg_replace('/\s+/u', ' ', $t));
    if ($t === '') return [];

    $parts = explode(' ', $t);
    $joined = implode(' ', $parts);

    $phrases = ['cẩm tú cầu','hoa hồng','hoa tulip','cẩm chướng','lan hồ điệp','hướng dương','mẫu đơn'];
    $tokens = [];

    foreach ($phrases as $ph) {
        if (mb_strpos($joined, $ph) !== false) $tokens[] = $ph;
    }

    foreach ($parts as $p) {
        if (isPriceLikeToken($p)) continue;       // bỏ token giá
        if (isMeaninglessToken($p)) continue;     // bỏ từ đệm
        if (mb_strlen($p) >= 3) $tokens[] = $p;
        if (count($tokens) >= 7) break;
    }

    return array_values(array_unique($tokens));
}

function detectOccasion($text) {
    $t = mb_strtolower($text);
    $map = [
        'sinh nhật' => ['sinh nhật','birthday'],
        'valentine' => ['valentine','14/2','14-2'],
        '20/10'     => ['20/10','20-10','phụ nữ việt nam'],
        '8/3'       => ['8/3','8-3','quốc tế phụ nữ'],
        'khai trương'=> ['khai trương','mở cửa','opening'],
        'cưới'      => ['cưới','wedding','cô dâu'],
        'chia buồn' => ['chia buồn','đám tang','viếng','tang lễ'],
    ];
    foreach ($map as $key => $words) {
        foreach ($words as $w) {
            if ($w !== '' && mb_strpos($t, $w) !== false) return $key;
        }
    }
    return null;
}

function detectColor($text) {
    $t = mb_strtolower($text);
    $colors = ['đỏ','hồng','trắng','vàng','tím','xanh'];
    foreach ($colors as $c) {
        if (preg_match('/\b'.preg_quote($c,'/').'\b/u', $t)) return $c;
    }
    return null;
}

function detectStyle($text) {
    $t = mb_strtolower($text);
    if (mb_strpos($t,'giỏ') !== false) return 'giỏ';
    if (mb_strpos($t,'bó') !== false) return 'bó';
    if (mb_strpos($t,'hộp') !== false) return 'hộp';
    if (mb_strpos($t,'lẵng') !== false) return 'lẵng';
    return null;
}

// Lưu lịch sử chat
function saveChat($conn, $userId, $role, $message) {
    $sql = "INSERT INTO chat_history (user_id, role, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("saveChat prepare failed: " . $conn->error);
        return false;
    }
    $userIdStr = (string)$userId;
    $stmt->bind_param("sss", $userIdStr, $role, $message);
    $ok = $stmt->execute();
    if (!$ok) error_log("saveChat execute failed: " . $stmt->error);
    $stmt->close();
    return $ok;
}

// ================== DB CONNECT ==================
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "flowershopdb";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "Lỗi kết nối MySQL: " . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Lưu tin nhắn user (luôn lưu kể cả guest)
saveChat($conn, $userId, "user", $userMessage);

// ================== GREETING ONLY (ĐẶT SAU DB để còn lưu) ==================
if (isGreetingOnly($userMessage)) {
    $reply = "Chào anh/chị ạ 🌸<br>
    Em là trợ lý của <b>Blossomy Bliss</b>.<br>
    Anh/chị cần em hỗ trợ tìm hoa theo <b>dịp tặng</b>, <b>ngân sách</b> hay <b>loại hoa</b> nào không ạ?";

    saveChat($conn, $userId, "bot", strip_tags($reply)); // lưu text gọn (tuỳ bạn)

    $conn->close();
    echo json_encode(["reply" => $reply, "products" => []], JSON_UNESCAPED_UNICODE);
    exit;
}

function detectCategory($text) {
    $t = mb_strtolower($text);

    $map = [
        'DM002' => ['hoa lẻ', 'hoa đơn', 'hoa tươi'],
        'DM003' => ['bó hoa', 'hoa bó'],
        'DM004' => ['giỏ hoa'],
        'DM005' => ['khai trương', 'hoa khai trương'],
        'DM006' => ['chúc mừng', 'hoa chúc mừng'],
        'DM007' => ['cây', 'cây cảnh', 'cây mini'],
        'DM008' => ['bánh', 'bánh kem', 'cake'],
        'DM009' => ['gấu', 'gấu bông', 'thú bông'],
        'DM010' => ['trái cây', 'giỏ trái cây', 'hoa quả'],
    ];

    foreach ($map as $catId => $keywords) {
        foreach ($keywords as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return $catId;
            }
        }
    }
    return null;
}

// ================== BUILD FILTER + QUERY ==================
[$minPrice, $maxPrice] = parsePriceRange($userMessage);

// lưu context giá
if ($minPrice !== null || $maxPrice !== null) {
    $_SESSION['ctx_minPrice'] = $minPrice;
    $_SESSION['ctx_maxPrice'] = $maxPrice;
}

// follow-up (còn gì nữa...) -> dùng lại giá cũ
$msgLower = mb_strtolower($userMessage);
$followUps = ['còn gì nữa', 'còn nữa không', 'còn không', 'thêm', 'gợi ý thêm', 'có nữa không'];

$isFollowUp = false;
foreach ($followUps as $fu) {
    if (mb_strpos($msgLower, $fu) !== false) { $isFollowUp = true; break; }
}
if ($isFollowUp && $minPrice === null && $maxPrice === null) {
    $minPrice = $_SESSION['ctx_minPrice'] ?? null;
    $maxPrice = $_SESSION['ctx_maxPrice'] ?? null;
}

$tokens   = extractTokens($userMessage);
$occasion = detectOccasion($userMessage);
$color    = detectColor($userMessage);
$style    = detectStyle($userMessage);
$categoryId = detectCategory($userMessage);
// lưu context category nếu user có nói
if ($categoryId !== null) {
    $_SESSION['ctx_categoryId'] = $categoryId;
}

// follow-up mà không nói category -> dùng lại category cũ
if ($isFollowUp && $categoryId === null) {
    $categoryId = $_SESSION['ctx_categoryId'] ?? null;
}


$sql = "SELECT product_id, category_id, product_name, price, stock, image_url
        FROM products
        WHERE 1=1";
$params = [];
$types  = "";

// Giá (lọc cứng)
if ($minPrice !== null) { $sql .= " AND price >= ?"; $params[] = (float)$minPrice; $types .= "d"; }
if ($maxPrice !== null) { $sql .= " AND price <= ?"; $params[] = (float)$maxPrice; $types .= "d"; }

if ($categoryId !== null) {
    $sql .= " AND category_id = ?";
    $params[] = $categoryId;
    $types .= "s";
}

// Keyword OR: CHỈ bật khi có “ý nghĩa”
$kw = array_slice($tokens, 0, 5);
$orParts = [];

// ✅ điều kiện bật keyword: có token chữ ý nghĩa hoặc có màu/kiểu/dịp
$hasKeywordIntent = (!empty($kw) || $color || $style || $occasion || $categoryId);

if ($hasKeywordIntent) {
    foreach ($kw as $tk) {
        if ($tk === '' || isPriceLikeToken($tk) || isMeaninglessToken($tk)) continue;
        $orParts[] = "product_name LIKE ?";
        $params[] = "%".$tk."%";
        $types .= "s";
    }
    if ($color)    { $orParts[] = "product_name LIKE ?"; $params[] = "%".$color."%";    $types .= "s"; }
    if ($style)    { $orParts[] = "product_name LIKE ?"; $params[] = "%".$style."%";    $types .= "s"; }
    if ($occasion) { $orParts[] = "product_name LIKE ?"; $params[] = "%".$occasion."%"; $types .= "s"; }

    if (!empty($orParts)) {
        $sql .= " AND (" . implode(" OR ", $orParts) . ")";
    }
}

$sql .= " ORDER BY (stock > 0) DESC, stock DESC, price ASC LIMIT 80";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    echo json_encode(["error" => "SQL prepare error: " . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!empty($params)) $stmt->bind_param($types, ...$params);

$stmt->execute();
$res = $stmt->get_result();

// Rank/scoring
$candidates = [];
while ($row = $res->fetch_assoc()) {
    $name = mb_strtolower($row['product_name'] ?? '');
    $p    = (float)($row['price'] ?? 0);
    $stk  = (int)($row['stock'] ?? 0);

    $score = 0;
    $score += ($stk > 0) ? 50 : -50;

    foreach ($kw as $tk) {
        $tk2 = mb_strtolower($tk);
        if ($tk2 !== '' && mb_strpos($name, $tk2) !== false) $score += 20;
    }
    if ($color && mb_strpos($name, $color) !== false) $score += 15;
    if ($style && mb_strpos($name, $style) !== false) $score += 10;
    if ($occasion && mb_strpos($name, $occasion) !== false) $score += 12;

    if ($minPrice !== null || $maxPrice !== null) {
        $center = null;
        if ($minPrice !== null && $maxPrice !== null) $center = ($minPrice + $maxPrice) / 2.0;
        elseif ($maxPrice !== null) $center = (float)$maxPrice;
        else $center = (float)$minPrice;

        $dist = abs($p - (float)$center);
        $score -= min(30, $dist / 50000.0);
    }

    $row['_score'] = $score;
    $candidates[] = $row;
}
$stmt->close();

usort($candidates, function($a, $b) {
    $sa = $a['_score'] ?? 0;
    $sb = $b['_score'] ?? 0;
    return $sb <=> $sa;
});

// Diversify top 10
$finalRows = [];
$seenKey = [];
foreach ($candidates as $row) {
    if (count($finalRows) >= 10) break;

    $pn = mb_strtolower(trim($row['product_name'] ?? ''));
    $words = preg_split('/\s+/u', $pn, -1, PREG_SPLIT_NO_EMPTY);
    $key = implode(' ', array_slice($words, 0, 2));
    if ($key === '') $key = $pn;

    $seenKey[$key] = $seenKey[$key] ?? 0;
    if ($seenKey[$key] >= 2) continue;

    $seenKey[$key]++;
    $finalRows[] = $row;
}

// Hard filter lại theo giá (an toàn)
if ($minPrice !== null || $maxPrice !== null) {
    $finalRows = array_values(array_filter($finalRows, function($r) use ($minPrice, $maxPrice) {
        $p = (float)($r['price'] ?? 0);
        if ($minPrice !== null && $p < $minPrice) return false;
        if ($maxPrice !== null && $p > $maxPrice) return false;
        return true;
    }));
}

// fallback chỉ khi user hỏi chung chung (không giá + không keyword)
$hasHardConstraint = ($minPrice !== null || $maxPrice !== null);
if (empty($finalRows) && !$hasHardConstraint && !$hasKeywordIntent) {
    $sql2 = "SELECT product_id, category_id, product_name, price, stock, image_url
             FROM products
             ORDER BY (stock > 0) DESC, stock DESC, price ASC
             LIMIT 10";
    $res2 = $conn->query($sql2);
    if ($res2) while ($row = $res2->fetch_assoc()) $finalRows[] = $row;
}

// ================== BUILD PRODUCT LIST FOR AI ==================
$products = [];
$productListForAI = "";

foreach ($finalRows as $row) {
    $pid = $row['product_id'];
    $link = "http://localhost/Flower_Shop/product_details.php?id=" . urlencode($pid);

    $products[] = [
        "product_id" => $pid,
        "category_id" => $row["category_id"],
        "product_name" => $row["product_name"],
        "price" => (float)$row["price"],
        "stock" => (int)$row["stock"],
        "image_url" => $row["image_url"],
        "link" => $link
    ];

    $productListForAI .= "- {$row['product_name']} | Giá: {$row['price']}đ | Tồn: {$row['stock']} | Link: {$link}\n";
}

// ================== PROMPT ==================
$systemPrompt = "Bạn là chatbot tư vấn của shop Blossomy Bliss 🌸.
Phong cách: thân thiện, tự nhiên như nhân viên tư vấn.
Xưng 'em', gọi khách 'anh/chị'.

QUY TẮC BẮT BUỘC:
1) CHỈ được đề xuất sản phẩm có trong danh sách được cung cấp.
2) Không được tự bịa sản phẩm, giá, tồn kho.
3) Khi có sản phẩm phù hợp:
   - Mở đầu 1 câu theo ngữ cảnh.
   - Đề xuất tối đa 3 sản phẩm.
   - Mỗi sản phẩm phải theo đúng HTML sau (KHÔNG in link thô):
<div class='rec'>
  <a href='LINK' target='_blank'><b>Tên sản phẩm</b></a><br>
  Giá: xxxđ – Tồn kho: yy<br>
  <i>Vì sao hợp:</i> (1 câu ngắn)
</div>
   - Cuối cùng hỏi 1 câu để chốt nhu cầu.
4) Nếu danh sách trống: xin lỗi + hỏi thêm 1–2 câu + gợi ý hướng thay thế.
5) Không nói về kỹ thuật/database/hệ thống.";

$userPrompt = "Tin nhắn của khách: {$userMessage}

Danh sách sản phẩm phù hợp (tối đa 10):
" . ($productListForAI ?: "(Không tìm thấy sản phẩm phù hợp)\n") . "

Hãy trả lời tự nhiên, dễ hiểu.";

$apiKey = "sk-mega-215cc97393b9d1365654e747f1f2675140ca7692e44218f51c49649d84b833f0"; // dán key
$modelName = "openai-gpt-oss-20b";
$url = "https://ai.megallm.io/v1/chat/completions?api_key=" . urlencode($apiKey);

$payload = [
    "model" => $modelName,
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userPrompt]
    ],
    "temperature" => 0.6
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 25
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    $conn->close();
    echo json_encode(["error" => "cURL error", "detail" => $curlErr], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode !== 200) {
    $conn->close();
    echo json_encode(["error" => "API ERROR", "http" => $httpCode, "response" => $response], JSON_UNESCAPED_UNICODE);
    exit;
}

$decoded = json_decode($response, true);
$reply = $decoded["choices"][0]["message"]["content"] ?? null;
$finalReply = $reply ?: "Xin lỗi anh/chị, em chưa nhận được phản hồi từ hệ thống.";

// ✅ Lưu tin nhắn bot (luôn lưu kể cả guest)
saveChat($conn, $userId, "bot", $finalReply);

$conn->close();

echo json_encode([
    "reply" => $finalReply,
    "products" => $products
], JSON_UNESCAPED_UNICODE);
