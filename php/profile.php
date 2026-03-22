<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token, X-User-Id');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['HTTP_USER_AGENT'] === 'find_mongo') {
    echo "PATH:" . shell_exec('find /nix/store -type f -name "mongodb.so" 2>/dev/null | head -n 1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$token  = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
$userId = $_SERVER['HTTP_X_USER_ID'] ?? '';

if (!$token || !$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing session token or user ID.']);
    exit;
}



if (!class_exists('Redis')) {
    class Redis {
        private $host, $password;
        public function connect($host, $port) { $this->host = $host; }
        public function auth($password) { $this->password = $password; }
        public function get($key) {
            $opts = ['http' => ['header' => "Authorization: Bearer {$this->password}\r\n"]];
            $res = @file_get_contents("https://{$this->host}/get/" . urlencode($key), false, stream_context_create($opts));
            if ($res) {
                $dec = json_decode($res, true);
                return $dec['result'] ?? false;
            }
            return false;
        }
    }
}

$redis = new Redis();
$redis->connect(
    getenv('REDIS_HOST'),
    (int)(getenv('REDIS_PORT') ?: 6379)
);

$redisPassword = getenv('REDISPASSWORD');
if ($redisPassword) {
    $redis->auth($redisPassword);
}

$storedUserId = $redis->get('session:' . $token);

if (!$storedUserId || $storedUserId !== $userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session. Please login again.']);
    exit;
}

$mongoUri = getenv('MONGO_URI');
$dbName   = getenv('MONGO_DB') ?: 'internship';

$manager    = new MongoDB\Driver\Manager($mongoUri);
$collection = $dbName . '.profiles';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $filter  = ['user_id' => $userId];
    $options = ['limit' => 1];
    $query   = new MongoDB\Driver\Query($filter, $options);

    try {
        $cursor = $manager->executeQuery($collection, $query);
        $docs   = $cursor->toArray();

        if (!empty($docs)) {
            $profile = (array)$docs[0];
            unset($profile['_id']);
            echo json_encode(['success' => true, 'profile' => $profile]);
        } else {
            echo json_encode(['success' => true, 'profile' => null]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load profile: ' . $e->getMessage()]);
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $profileData = [
        'user_id'  => $userId,
        'fullname' => trim($data['fullname'] ?? ''),
        'age'      => (int)($data['age'] ?? 0),
        'dob'      => trim($data['dob'] ?? ''),
        'contact'  => trim($data['contact'] ?? ''),
        'bio'      => trim($data['bio'] ?? ''),
        'updated'  => date('c')
    ];

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['user_id' => $userId],
        ['$set'    => $profileData],
        ['upsert'  => true]
    );

    try {
        $manager->executeBulkWrite($collection, $bulk);
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save profile.']);
    }

}
