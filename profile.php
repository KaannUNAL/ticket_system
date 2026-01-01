<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';
$userId = getUserId();

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $error = 'Ad ve e-posta alanları zorunludur.';
    } else {
        // E-posta kontrolü
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        
        if ($stmt->fetch()) {
            $error = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $userId]);
            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $success = 'Profil bilgileriniz güncellendi.';
            $user['name'] = $name;
            $user['email'] = $email;
        }
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Tüm şifre alanlarını doldurun.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Yeni şifreler eşleşmiyor.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır.';
    } else {
        if (password_verify($current_password, $user['password'])) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $userId]);
            
            $success = 'Şifreniz başarıyla değiştirildi.';
        } else {
            $error = 'Mevcut şifreniz hatalı.';
        }
    }
}

// Kullanıcının ticket istatistikleri
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM tickets WHERE user_id = ?");
$stmt->execute([$userId]);
$ticket_stats = $stmt->fetch();

include 'header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Profil Sidebar -->
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                    <h5><?php echo sanitize($user['name']); ?></h5>
                    <p class="text-muted"><?php echo sanitize($user['email']); ?></p>
                    <?php echo getRoleBadge($user['role']); ?>
                    <hr>
                    <div class="text-left">
                        <p class="mb-1"><small><strong>Üyelik Tarihi:</strong></small></p>
                        <p><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar"></i> Ticket İstatistiklerim</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary"><?php echo $ticket_stats['total']; ?></h4>
                            <small>Toplam</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning"><?php echo $ticket_stats['open']; ?></h4>
                            <small>Açık</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success"><?php echo $ticket_stats['completed']; ?></h4>
                            <small>Çözülen</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Profil Bilgileri -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="fas fa-user-edit"></i> Profil Bilgilerini Düzenle</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Ad Soyad</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo sanitize($user['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> E-posta</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo sanitize($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Güncelle
                        </button>
                    </form>
                </div>
            </div>

            <!-- Şifre Değiştirme -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-lock"></i> Şifre Değiştir</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Mevcut Şifre</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Yeni Şifre</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                    <small class="form-text text-muted">En az 6 karakter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Yeni Şifre (Tekrar)</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Şifreyi Değiştir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>