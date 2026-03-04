<?php  //pro_letter/functions.php
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $dbHost = 'localhost';
        $dbName = 'pro_letter';
        $dbUser = 'root';
        $dbPass = '';
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                           $dbUser, $dbPass,
                           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

function login(string $username, string $password): array {
    try {
        $pdo = getPDO(); // หรือ db()

        // 1) หา user จาก username เท่านั้น
        $sql = "
            SELECT u.user_id, u.username, u.password, u.fullname, u.position, u.role_id, u.is_active,
                   r.role_name
            FROM users u
            LEFT JOIN roles r ON r.role_id = u.role_id
            WHERE u.username = :username
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            return ['ok' => false, 'error' => 'user'];
        }

        if ((int)$u['is_active'] !== 1) {
            return ['ok' => false, 'error' => 'inactive'];
        }

        // 2) ตรวจ password (รองรับทั้ง bcrypt และ plaintext)
        $dbPass = (string)$u['password'];

        $isHash = str_starts_with($dbPass, '$2y$') || str_starts_with($dbPass, '$argon2');
        $passOk = $isHash ? password_verify($password, $dbPass) : hash_equals($dbPass, $password);

        if (!$passOk) {
            return ['ok' => false, 'error' => 'pass'];
        }

        // 3) คืนค่าที่ callfunction.php ใช้
        return [
            'ok'        => true,
            'user_id'   => (int)$u['user_id'],
            'username'  => $u['username'],
            'role_id'   => (int)$u['role_id'],
            'fullname'  => $u['fullname'],
            'position'  => $u['position'],
            'role_name' => $u['role_name'] ?? '',
        ];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'db'];
    }
}

function getAllUsers() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT user_id, username, password, fullname, email, role_id, position, created_at, is_active 
                         FROM users ORDER BY user_id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addLog($userId, $action) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
    $stmt->execute([$userId, $action]);
}

function getActiveUsers() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT user_id, username, fullname, email, role_id, position, created_at, is_active 
                         FROM users 
                         WHERE is_active = 1
                         ORDER BY user_id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db(): PDO {
    $dbHost = 'localhost';
    $dbName = 'pro_letter';
    $dbUser = 'root';
    $dbPass = '';
    return new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

// ✅ ฟังก์ชันเพิ่มเมนู "กำหนดสิทธิ์"
function renderAdminExtraMenus() {
    $current = basename($_SERVER['PHP_SELF']); // ดึงชื่อไฟล์ปัจจุบัน เช่น home.php
?>
<a href="/Pro_letter/user_Managerment.php">
    <div class="px-4 py-2 rounded-[11px] font-bold transition 
            <?= ($current === 'user_Managerment.php') 
                ? 'bg-white text-teal-500 shadow' 
                : 'text-white hover:bg-white hover:text-teal-500' ?>">
        กำหนดสิทธิ์
    </div>
</a>
<?php
}