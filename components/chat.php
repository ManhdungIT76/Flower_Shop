<?php
session_start();

header("Content-Type: application/json; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ================== INPUT ==================
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($data["message"] ?? "");

if (!isset($_SESSION['ctx_waiting_occasion'])) $_SESSION['ctx_waiting_occasion'] = 0;
if (!isset($_SESSION['ctx_waiting_group'])) $_SESSION['ctx_waiting_group'] = 0;

if ($userMessage === "") {
    echo json_encode(["error" => "Không nhận được tin nhắn từ client"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== LOGIN / GUEST ID ==================
$userId = "";
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $userId = (string)$_SESSION['user']['id'];
} else {
    $userId = "GUEST_" . session_id();
}
$isLoggedIn = (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id']));

// ================== HELPERS ==================
function isGreetingOnly($text) {
    $t = mb_strtolower(trim($text));
    $t = preg_replace('/[^\p{L}\p{N}\s]/u', '', $t);

    // có số tiền/giá => không coi là greeting
    if (preg_match('/\b(\d{1,3}\s*k|\d{4,})\b/u', $t)) return false;
    if (mb_strpos($t, 'dưới') !== false || mb_strpos($t, 'trên') !== false || mb_strpos($t, 'từ') !== false) return false;

    $greetings = ['chào','chào shop','chào bạn','hello','hi','hey','xin chào','alo','ad ơi','shop ơi'];
    foreach ($greetings as $g) if ($t === $g) return true;

    if (mb_strlen($t) <= 10 && !preg_match('/hoa|giá|mua|tặng|bó|giỏ|bánh|gấu|trái/u', $t)) return true;
    return false;
}

function moneyToInt($s) {
    $s = mb_strtolower(trim($s));
    $s = str_replace([',', '.', 'đ', 'vnđ', 'vnd', ' '], '', $s);

    if (function_exists('str_ends_with') && str_ends_with($s, 'k')) return (float)rtrim($s,'k') * 1000;
    if (!function_exists('str_ends_with') && substr($s, -1) === 'k') return (float)rtrim($s,'k') * 1000;

    return (float)preg_replace('/[^\d]/', '', $s);
}

function parsePriceRange($text) {
    $t = mb_strtolower($text);
    $min = null; $max = null;

    if (preg_match('/từ\s*([\d\., ]+k?)\s*(đ|vnd|vnđ)?\s*đến\s*([\d\., ]+k?)/iu', $t, $m)) {
        $min = moneyToInt($m[1]);
        $max = moneyToInt($m[3]);
        return [$min, $max];
    }

    if (preg_match('/\b(\d{1,3})\s*k\s*[-–]\s*(\d{1,3})\s*k\b/iu', $t, $m)) {
        $min = (float)$m[1] * 1000;
        $max = (float)$m[2] * 1000;
        return [$min, $max];
    }

    if (preg_match('/(dưới|<=|<)\s*([\d\., ]+k?)/iu', $t, $m)) {
        $max = moneyToInt($m[2]);
        return [null, $max];
    }

    if (preg_match('/(trên|>=|>)\s*([\d\., ]+k?)/iu', $t, $m)) {
        $min = moneyToInt($m[2]);
        return [$min, null];
    }

    if (preg_match('/\b(\d{4,})\b/u', $t, $m)) {
        if (mb_strpos($t, 'dưới') !== false) return [null, (float)$m[1]];
        if (mb_strpos($t, 'trên') !== false) return [(float)$m[1], null];
    }

    return [null, null];
}

function isPriceLikeToken($tk) {
    $tk = mb_strtolower(trim($tk));
    if (preg_match('/^\d+$/u', $tk)) return true;
    if (preg_match('/^\d+k$/u', $tk)) return true;
    return false;
}

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

    $stop = [
        'tôi','mình','em','anh','chị','bạn','shop','ad','admin','chủ shop',
        'tìm','tìm kiếm','kiếm','xem','coi','chọn','mua','đặt','order','lấy',
        'giúp','giúp tôi','giúp mình','hỗ trợ','tư vấn','cho','cho tôi','cho mình',
        'một','vài','mấy','nhiều','ít','tất cả','toàn bộ','bất kỳ',
        'khoảng','tầm','tầm khoảng','tầm giá',
        'không','không ạ','không nhỉ','được không','được ko','ko','k','hok',
        'nhỉ','ạ','ơi','vậy','thế','nào','gì','sao','không biết',
        'sản','phẩm','sản phẩm','mặt hàng','item','items','sp','hàng',
        'với','và','hay','hoặc','là','thì','mà',
        'giá','giá cả','bao nhiêu','tiền','đồng','vnđ','vnd','đ',
        'rẻ','rẻ nhất','cao','thấp',
        'dưới','trên','từ','đến','<=','>=','<','>',
        'loại','mẫu','kiểu','dạng','size','form','phong cách',
        'hoa','bó','giỏ','lẵng','kệ','chậu','cây',
        'vui lòng','làm ơn','nhé','giùm','giúp với',
        'còn','nữa','thêm','gợi ý','đề xuất','recommend'
    ];

    foreach ($stop as $w) {
        $t = preg_replace('/\b'.preg_quote($w,'/').'\b/u', ' ', $t);
    }

    $t = trim(preg_replace('/\s+/u', ' ', $t));
    if ($t === '') return [];

    $parts = explode(' ', $t);
    $joined = implode(' ', $parts);

    $phrases = ['cẩm tú cầu','hoa hồng','hoa tulip','cẩm chướng','lan hồ điệp','hướng dương','mẫu đơn','bánh kem','gấu bông','trái cây'];
    $tokens = [];

    foreach ($phrases as $ph) {
        if (mb_strpos($joined, $ph) !== false) $tokens[] = $ph;
    }

    foreach ($parts as $p) {
        if (isPriceLikeToken($p)) continue;
        if (isMeaninglessToken($p)) continue;
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
    ];
    foreach ($map as $key => $words) {
        foreach ($words as $w) if ($w !== '' && mb_strpos($t, $w) !== false) return $key;
    }
    return null;
}

function detectColor($text) {
    $t = mb_strtolower($text);
    $colors = ['đỏ','hồng','trắng','vàng','tím','xanh'];
    foreach ($colors as $c) if (preg_match('/\b'.preg_quote($c,'/').'\b/u', $t)) return $c;
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

function isFollowUpMessage($text) {
    $t = mb_strtolower(trim($text));
    if (preg_match('/^\s*(thế\s+)?còn\b/iu', $t)) return true;
    if (preg_match('/^\s*(vậy\s+)?còn\b/iu', $t)) return true;
    if (preg_match('/^\s*nếu\b/iu', $t)) return true;
    if (preg_match('/\bthì\s*sao\b/iu', $t)) return true;

    $followUps = ['còn gì nữa', 'còn nữa không', 'còn không', 'thêm', 'gợi ý thêm', 'có nữa không', 'xem thêm'];
    foreach ($followUps as $fu) if (mb_strpos($t, $fu) !== false) return true;
    return false;
}

function isOnlyPriceChange($text) {
    $t = mb_strtolower($text);
    if (!preg_match('/\b(\d{1,3}\s*k|\d{4,})\b/u', $t)) return false;

    // nếu có nhắc rõ loại thì không phải "chỉ giá"
    if (preg_match('/\b(hoa|bánh|bánh kem|gấu|gấu bông|trái|trái cây|giỏ|bó|lẵng|cây)\b/iu', $t)) return false;
    return true;
}

function isMoreRequest($text) {
    $t = mb_strtolower($text);
    $more = ['còn gì nữa', 'còn nữa không', 'thêm', 'gợi ý thêm', 'có nữa không', 'xem thêm'];
    foreach ($more as $m) if (mb_strpos($t, $m) !== false) return true;
    return false;
}

// ====== GROUP ======
function detectGroup($text) {
    $t = mb_strtolower($text);
    if (preg_match('/\b(gấu|gấu bông|thú bông)\b/u', $t)) return 'bear';
    if (preg_match('/\b(bánh|bánh kem|cake)\b/u', $t)) return 'cake';
    if (preg_match('/\b(trái cây|hoa quả|giỏ trái cây)\b/u', $t)) return 'fruit';
    if (preg_match('/\bhoa\b/u', $t)) return 'flower';
    return null;
}

function getGroupCategoryIds($group) {
    $map = [
        'flower' => ['DM002','DM003','DM004','DM005','DM006'],
        'bear'   => ['DM009'],
        'cake'   => ['DM008'],
        'fruit'  => ['DM010'],
    ];
    return $map[$group] ?? [];
}

function occasionToCategories($occasion) {
    // KHÔNG ép DM002 nếu muốn tránh trả sai loại
    $map = [
        'sinh nhật'   => ['DM003','DM004','DM006'],
        'valentine'   => ['DM003','DM004'],
        '8/3'         => ['DM003','DM004'],
        '20/10'       => ['DM003','DM004'],
        'khai trương' => ['DM005'],
        'cưới'        => ['DM006'],
    ];
    return $map[$occasion] ?? [];
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
            if (mb_strpos($t, $kw) !== false) return $catId;
        }
    }
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

function loadRecentChatForAI($conn, $userId, $limit = 4, $offset = 1) {
    $rows = [];
    $uid = $conn->real_escape_string($userId);

    $sql = "SELECT role, message
            FROM chat_history
            WHERE user_id = '$uid'
            ORDER BY id DESC
            LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $rs = $conn->query($sql);
    if (!$rs) return [];

    while ($r = $rs->fetch_assoc()) {
        $role = ($r['role'] === 'bot') ? 'assistant' : 'user';
        $msg  = (string)($r['message'] ?? '');
        if ($msg !== '') $rows[] = ["role" => $role, "content" => $msg];
    }
    return array_reverse($rows);
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

if (!isset($_SESSION['ctx_seen_ids'])) $_SESSION['ctx_seen_ids'] = [];
if (!isset($_SESSION['ctx_offset'])) $_SESSION['ctx_offset'] = 0;

// ✅ Lưu tin nhắn user
if ($isLoggedIn) saveChat($conn, $userId, "user", $userMessage);

// ================== GREETING ==================
if (isGreetingOnly($userMessage)) {
    $reply = "Chào anh/chị ạ 🌸<br>
    Em là trợ lý của <b>Blossomy Bliss</b>.<br>
    Anh/chị cần em hỗ trợ tìm hoa theo <b>dịp tặng</b>, <b>ngân sách</b> hay <b>loại hoa</b> nào không ạ?";

    if ($isLoggedIn) saveChat($conn, $userId, "bot", strip_tags($reply));
    $conn->close();
    echo json_encode(["reply" => $reply, "products" => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== CATEGORY LIST INTENT ==================
if (preg_match('/(danh mục|loại sản phẩm|shop có những danh mục|shop có những gì|bán những gì)/iu', $userMessage)) {

    $sql = "SELECT category_name FROM categories ORDER BY category_name";
    $rs = $conn->query($sql);

    $cats = [];
    if ($rs) {
        while ($r = $rs->fetch_assoc()) $cats[] = $r['category_name'];
    }

    if (!empty($cats)) {
        $reply = "Hiện tại shop có các danh mục sau ạ:<br>• " . implode("<br>• ", $cats);
    } else {
        $reply = "Hiện tại shop chưa cấu hình danh mục sản phẩm ạ.";
    }

    if ($isLoggedIn) saveChat($conn, $userId, "bot", strip_tags($reply));
    $conn->close();

    echo json_encode(["reply" => $reply, "products" => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== BUILD FILTER (ORDER FIXED) ==================
$t = mb_strtolower($userMessage);

[$minPrice, $maxPrice] = parsePriceRange($userMessage);
$isFollowUp = isFollowUpMessage($userMessage);
$isMore = ($isFollowUp && isMoreRequest($userMessage));

// ---- detect intent FIRST (để không dùng biến chưa khởi tạo) ----
$tokens     = extractTokens($userMessage);
$occasionNow = detectOccasion($userMessage);
$occasion   = $occasionNow;
$color      = detectColor($userMessage);
$style      = detectStyle($userMessage);
$categoryId = detectCategory($userMessage);
$group      = detectGroup($userMessage);
$kw         = array_slice($tokens, 0, 5);

// ---- occasion context ----
$askingOccasion = (mb_strpos($t, 'dịp') !== false || mb_strpos($t, 'tặng') !== false);

if ($askingOccasion && $occasionNow === null && empty($_SESSION['ctx_occasion'])) {
    $_SESSION['ctx_waiting_occasion'] = 1;
    $reply = "Anh/chị tặng dịp nào ạ? (sinh nhật / valentine / 8/3 / 20/10 / khai trương / cưới / chia buồn)";
    if ($isLoggedIn) saveChat($conn, $userId, "bot", strip_tags($reply));
    $conn->close();
    echo json_encode(["reply" => $reply, "products" => []], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SESSION['ctx_waiting_occasion'] == 1 && $occasionNow !== null) {
    $_SESSION['ctx_occasion'] = $occasionNow;
    $_SESSION['ctx_waiting_occasion'] = 0;
    $_SESSION['ctx_offset'] = 0;
    $_SESSION['ctx_seen_ids'] = [];
}

// dùng lại occasion cũ nếu không nhắc
if ($occasion === null && !empty($_SESSION['ctx_occasion'])) $occasion = $_SESSION['ctx_occasion'];
if ($occasion !== null) $_SESSION['ctx_occasion'] = $occasion;

// ---- group/category context ----
if ($group !== null) $_SESSION['ctx_group'] = $group;
if ($categoryId !== null) $_SESSION['ctx_categoryId'] = $categoryId;
if (!empty($kw)) $_SESSION['ctx_kw'] = $kw;

// ưu tiên group theo category nếu user nói rõ
if ($categoryId !== null) {
    if ($categoryId === 'DM009') $_SESSION['ctx_group'] = 'bear';
    else if ($categoryId === 'DM008') $_SESSION['ctx_group'] = 'cake';
    else if ($categoryId === 'DM010') $_SESSION['ctx_group'] = 'fruit';
    else if (in_array($categoryId, getGroupCategoryIds('flower'), true)) $_SESSION['ctx_group'] = 'flower';
}

// nếu bot đang hỏi group (vì user chỉ nói giá)
if (!empty($_SESSION['ctx_waiting_group'])) {
    $g = detectGroup($userMessage);
    if ($g !== null) {
        $_SESSION['ctx_group'] = $g;
        $_SESSION['ctx_waiting_group'] = 0;
        $_SESSION['ctx_offset'] = 0;
        $_SESSION['ctx_seen_ids'] = [];
    }
}

// ---- chỉ nói giá -> dùng lại ngữ cảnh hoặc hỏi loại ----
if (isOnlyPriceChange($userMessage)) {
    $hasContext = !empty($_SESSION['ctx_kw']) || !empty($_SESSION['ctx_group']) || !empty($_SESSION['ctx_categoryId']) || !empty($_SESSION['ctx_occasion']);

    if (!$hasContext) {
        $_SESSION['ctx_waiting_group'] = 1;
        $reply = "Anh/chị muốn tìm theo mức giá này cho loại sản phẩm nào ạ? (hoa / bánh / gấu / trái cây)";
        if ($isLoggedIn) saveChat($conn, $userId, "bot", strip_tags($reply));
        $conn->close();
        echo json_encode(["reply" => $reply, "products" => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // có ngữ cảnh -> dùng lại
    if (empty($kw) && !empty($_SESSION['ctx_kw'])) $kw = $_SESSION['ctx_kw'];
    if ($categoryId === null && !empty($_SESSION['ctx_categoryId'])) $categoryId = $_SESSION['ctx_categoryId'];
    if ($group === null && !empty($_SESSION['ctx_group'])) $group = $_SESSION['ctx_group'];
    if ($occasion === null && !empty($_SESSION['ctx_occasion'])) $occasion = $_SESSION['ctx_occasion'];
}

// ---- lưu context giá ----
if ($minPrice !== null || $maxPrice !== null) {
    $_SESSION['ctx_minPrice'] = $minPrice;
    $_SESSION['ctx_maxPrice'] = $maxPrice;
}

// follow-up mà không nói giá mới -> dùng lại
if ($isFollowUp && $minPrice === null && $maxPrice === null) {
    $minPrice = $_SESSION['ctx_minPrice'] ?? null;
    $maxPrice = $_SESSION['ctx_maxPrice'] ?? null;
}

// đổi giá (không phải "còn gì nữa") -> reset offset + reset seen
if (($minPrice !== null || $maxPrice !== null) && !$isMore) {
    $_SESSION['ctx_offset'] = 0;
    $_SESSION['ctx_seen_ids'] = [];
}

// "còn gì nữa" -> tăng offset
if ($isMore) {
    $_SESSION['ctx_offset'] += 10;
}
$offset = (int)$_SESSION['ctx_offset'];

// hasKeywordIntent
$hasTextFilter = (!empty($kw) || $color || $style);

// ================== QUERY ==================
$sql = "SELECT product_id, category_id, product_name, price, stock, image_url
        FROM products
        WHERE 1=1";
$params = [];
$types  = "";

// giá
if ($minPrice !== null) { $sql .= " AND price >= ?"; $params[] = (float)$minPrice; $types .= "d"; }
if ($maxPrice !== null) { $sql .= " AND price <= ?"; $params[] = (float)$maxPrice; $types .= "d"; }

// category cụ thể
if ($categoryId !== null) {
    $sql .= " AND category_id = ?";
    $params[] = $categoryId;
    $types .= "s";
}

// group filter (ưu tiên group khi đã có)
$groupSess = $_SESSION['ctx_group'] ?? null;
if ($categoryId === null && $groupSess !== null) {
    $groupCats = getGroupCategoryIds($groupSess);
    if (!empty($groupCats)) {
        $placeholders = implode(',', array_fill(0, count($groupCats), '?'));
        $sql .= " AND category_id IN ($placeholders)";
        foreach ($groupCats as $c) { $params[] = $c; $types .= "s"; }
    }
} else {
    // chỉ dùng occasion filter khi KHÔNG có group/category
    if ($categoryId === null && $occasion) {
        $ocats = occasionToCategories($occasion);
        if (!empty($ocats)) {
            $placeholders = implode(',', array_fill(0, count($ocats), '?'));
            $sql .= " AND category_id IN ($placeholders)";
            foreach ($ocats as $c) { $params[] = $c; $types .= "s"; }
        }
    }
}

// loại sản phẩm đã gợi ý: chỉ loại khi user xin thêm ("còn gì nữa")
$seenIds = $_SESSION['ctx_seen_ids'] ?? [];
if ($isMore && !empty($seenIds)) {
    $seenIds = array_values(array_unique($seenIds));
    $placeholders = implode(',', array_fill(0, count($seenIds), '?'));
    $sql .= " AND product_id NOT IN ($placeholders)";
    foreach ($seenIds as $id) { $params[] = $id; $types .= "s"; }
}

// keyword OR
if ($hasTextFilter) {
    $orParts = [];

    foreach ($kw as $tk) {
        if ($tk === '' || isPriceLikeToken($tk) || isMeaninglessToken($tk)) continue;
        $orParts[] = "product_name LIKE ?";
        $params[] = "%".$tk."%";
        $types .= "s";
    }
    if ($color)    { $orParts[] = "product_name LIKE ?"; $params[] = "%".$color."%";    $types .= "s"; }
    if ($style)    { $orParts[] = "product_name LIKE ?"; $params[] = "%".$style."%";    $types .= "s"; }
    // KHÔNG thêm $occasion vào LIKE để tránh lọc rỗng (vì tên SP thường không chứa "sinh nhật/valentine")

    if (!empty($orParts)) $sql .= " AND (" . implode(" OR ", $orParts) . ")";
}

$sql .= " ORDER BY (stock > 0) DESC, stock DESC, price ASC LIMIT 80 OFFSET ?";
$params[] = $offset;
$types .= "i";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    echo json_encode(["error" => "SQL prepare error: " . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// ================== RANKING ==================
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
    return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
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

// hard filter giá
if ($minPrice !== null || $maxPrice !== null) {
    $finalRows = array_values(array_filter($finalRows, function($r) use ($minPrice, $maxPrice) {
        $p = (float)($r['price'] ?? 0);
        if ($minPrice !== null && $p < $minPrice) return false;
        if ($maxPrice !== null && $p > $maxPrice) return false;
        return true;
    }));
}

// fallback chỉ khi hỏi chung chung và không có intent
if (empty($finalRows) && ($minPrice === null && $maxPrice === null) && !$hasKeywordIntent) {
    $sql2 = "SELECT product_id, category_id, product_name, price, stock, image_url
             FROM products
             ORDER BY (stock > 0) DESC, stock DESC, price ASC
             LIMIT 10";
    $res2 = $conn->query($sql2);
    if ($res2) while ($row = $res2->fetch_assoc()) $finalRows[] = $row;
}

// cập nhật danh sách đã gợi ý: chỉ để phục vụ "còn gì nữa" tránh lặp
foreach ($finalRows as $r) {
    if (!empty($r['product_id'])) $_SESSION['ctx_seen_ids'][] = $r['product_id'];
}
$_SESSION['ctx_seen_ids'] = array_values(array_unique($_SESSION['ctx_seen_ids']));
$_SESSION['ctx_seen_ids'] = array_slice($_SESSION['ctx_seen_ids'], -200);

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

// ================== CALL AI ==================
$apiKey = "sk-mega-dc457e6da99886b50bebac679c212a4fdbe7ea3f0b21c2521147c5abd6f98c43"; // dán key
$modelName = "openai-gpt-oss-20b";
$url = "https://ai.megallm.io/v1/chat/completions?api_key=" . urlencode($apiKey);

// đưa lịch sử chat vào messages
$historyMsgs = $isLoggedIn ? loadRecentChatForAI($conn, $userId, 4, 1) : [];

$messages = array_merge(
    [["role" => "system", "content" => $systemPrompt]],
    $historyMsgs,
    [["role" => "user", "content" => $userPrompt]]
);

$payload = [
    "model" => $modelName,
    "messages" => $messages,
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

// lưu tin nhắn bot
if ($isLoggedIn) saveChat($conn, $userId, "bot", $finalReply);

$conn->close();

echo json_encode([
    "reply" => $finalReply,
    "products" => $products
], JSON_UNESCAPED_UNICODE);
