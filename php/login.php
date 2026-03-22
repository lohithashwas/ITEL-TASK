<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$mysqli = new mysqli(
    getenv('MYSQLHOST'),
    getenv('MYSQLUSER'),
    getenv('MYSQLPASSWORD'),
    getenv('MYSQLDATABASE'),
    (int)(getenv('MYSQLPORT') ?: 3306)
);

if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}
$stmt = $mysqli->prepare('SELECT id, password_hash FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($userId, $hash);
$stmt->fetch();
$stmt->close();
$mysqli->close();

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'No account found with this email.']);
    exit;
}

if (!password_verify($password, $hash)) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

if (!class_exists('Redis')) {
    class Redis {
        private $host, $password;
        public function connect($host, $port) { $this->host = $host; }
        public function auth($password) { $this->password = $password; }
        public function setex($key, $ttl, $value) {
            $opts = ['http' => ['header' => "Authorization: Bearer {$this->password}\r\n"]];
            @file_get_contents("https://{$this->host}/setex/" . urlencode($key) . "/{$ttl}/" . urlencode($value), false, stream_context_create($opts));
        }
    }
}

$token = bin2hex(random_bytes(32));

$redis = new Redis();
$redis->connect(
    getenv('REDIS_HOST'),
    (int)(getenv('REDIS_PORT') ?: 6379)
);

$redisPassword = getenv('REDISPASSWORD');
if ($redisPassword) {
    $redis->auth($redisPassword);
}

$redis->setex('session:' . $token, 3600, (string)$userId);

echo json_encode([
    'success' => true,
    'token'   => $token,
    'user_id' => (string)$userId
]);
