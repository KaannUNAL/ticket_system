<?php
session_start();

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ticket_system');

// Site ayarları
define('SITE_NAME', 'Tankmarine Ticket Sistemi');
define('SITE_URL', 'http://localhost');

// Departmanları veritabanından çek
function getDepartments()
{
    global $pdo;
    static $departments = null;

    if ($departments === null) {
        $stmt = $pdo->query("SELECT code, name FROM departments WHERE is_active = 1 ORDER BY name");
        $departments = [];
        while ($row = $stmt->fetch()) {
            $departments[$row['code']] = $row['name'];
        }
    }

    return $departments;
}

// Veritabanı bağlantısı
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Yardımcı fonksiyonlar
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isManager()
{
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager');
}

function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function getUserRole()
{
    return $_SESSION['role'] ?? null;
}

function getUserDepartment()
{
    return $_SESSION['department'] ?? null;
}

function redirect($page)
{
    header("Location: $page");
    exit;
}

function generateTicketNumber()
{
    return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
}

function getStatusBadge($status)
{
    $badges = [
        'open' => '<span class="badge badge-primary">Açık</span>',
        'assigned' => '<span class="badge badge-info">Atandı</span>',
        'in_progress' => '<span class="badge badge-warning">İşlemde</span>',
        'waiting' => '<span class="badge badge-secondary">Beklemede</span>',
        'completed' => '<span class="badge badge-success">Tamamlandı</span>',
        'cancelled' => '<span class="badge badge-danger">İptal</span>'
    ];
    return $badges[$status] ?? $status;
}

function getPriorityBadge($priority)
{
    $badges = [
        'low' => '<span class="badge badge-success">Düşük</span>',
        'medium' => '<span class="badge badge-info">Orta</span>',
        'high' => '<span class="badge badge-warning">Yüksek</span>',
        'urgent' => '<span class="badge badge-danger">Acil</span>'
    ];
    return $badges[$priority] ?? $priority;
}

function getRoleBadge($role)
{
    $badges = [
        'admin' => '<span class="badge badge-danger">Admin</span>',
        'manager' => '<span class="badge badge-warning">Yönetici</span>',
        'user' => '<span class="badge badge-info">Kullanıcı</span>'
    ];
    return $badges[$role] ?? $role;
}

function getDepartmentName($dept)
{
    $departments = getDepartments();
    return $departments[$dept] ?? $dept;
}

function getDepartmentBadge($dept)
{
    $colors = [
        'muhasebe' => 'primary',
        'operasyon' => 'success',
        'insan_kaynaklari' => 'info',
        'teknik' => 'warning',
        'genel' => 'secondary'
    ];
    $color = $colors[$dept] ?? 'secondary';
    return '<span class="badge badge-' . $color . '">' . getDepartmentName($dept) . '</span>';
}

function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'Az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 604800) return floor($diff / 86400) . ' gün önce';

    return date('d.m.Y H:i', $timestamp);
}

function sanitize($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Bildirim oluşturma fonksiyonu
function createNotification($user_id, $ticket_id, $type, $message)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, ticket_id, type, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $ticket_id, $type, $message]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Departman kullanıcılarına bildirim gönder
function notifyDepartmentUsers($ticket_id, $department, $type, $message, $exclude_user_id = null)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE department = ? AND id != ?");
    $stmt->execute([$department, $exclude_user_id ?? 0]);

    while ($user = $stmt->fetch()) {
        createNotification($user['id'], $ticket_id, $type, $message);
    }
}

// Adminlere bildirim gönder
function notifyAdmins($ticket_id, $type, $message, $exclude_user_id = null)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
    $stmt->execute([$exclude_user_id ?? 0]);

    while ($user = $stmt->fetch()) {
        createNotification($user['id'], $ticket_id, $type, $message);
    }
}

// Okunmamış bildirim sayısı
function getUnreadNotificationCount($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

// Oturum kontrolü
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireManager()
{
    requireLogin();
    if (!isManager()) {
        redirect('index.php');
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php');
    }
}

// Ticket görüntüleme yetkisi kontrolü - HERKES TÜM TICKETLARı GÖREBİLİR
function canViewTicket($ticket, $userId, $userRole, $userDept)
{
    return true; // Herkes tüm ticketları görebilir
}

// Ticket detayına erişim kontrolü - Sadece kendi departmanı veya kendi ticketı
function canViewTicketDetail($ticket, $userId, $userRole, $userDept)
{
    // Admin her şeyi görebilir
    if ($userRole === 'admin') {
        return true;
    }

    // Kendi ticketını herkes görebilir
    if ($ticket['user_id'] == $userId) {
        return true;
    }

    // Yönetici ve kullanıcı kendi departmanının ticketlarını görebilir
    global $pdo;
    if ($ticket['category_id']) {
        $stmt = $pdo->prepare("SELECT department FROM categories WHERE id = ?");
        $stmt->execute([$ticket['category_id']]);
        $category = $stmt->fetch();
        if ($category && $category['department'] === $userDept) {
            return true;
        }
    }

    return false;
}

// Ticket silme yetkisi kontrolü
function canDeleteTicket($ticket, $userId, $userRole, $userDept)
{
    // Admin her şeyi silebilir
    if ($userRole === 'admin') {
        return true;
    }

    // Kendi ticketını herkes silebilir
    if ($ticket['user_id'] == $userId) {
        return true;
    }

    // Yönetici kendi departmanının ticketlarını silebilir
    if ($userRole === 'manager') {
        global $pdo;
        if ($ticket['category_id']) {
            $stmt = $pdo->prepare("SELECT department FROM categories WHERE id = ?");
            $stmt->execute([$ticket['category_id']]);
            $category = $stmt->fetch();
            if ($category && $category['department'] === $userDept) {
                return true;
            }
        }
    }

    return false;
}
