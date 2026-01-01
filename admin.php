<?php
require_once 'config.php';
requireAdmin();

$tab = $_GET['tab'] ?? 'users';
$error = '';
$success = '';

// Departman ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $code = strtolower(trim($_POST['code']));
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Code formatı kontrolü (sadece küçük harf ve alt çizgi)
    if (!preg_match('/^[a-z_]+$/', $code)) {
        $error = 'Departman kodu sadece küçük harf ve alt çizgi içerebilir.';
    } elseif (empty($name)) {
        $error = 'Departman adı zorunludur.';
    } else {
        $check = $pdo->prepare("SELECT id FROM departments WHERE code = ?");
        $check->execute([$code]);

        if ($check->fetch()) {
            $error = 'Bu departman kodu zaten kullanılıyor.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (code, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $description]);
            $success = 'Departman başarıyla eklendi.';
        }
    }
}

// Departman silme
if (isset($_GET['delete_department'])) {
    $dept_code = $_GET['delete_department'];

    // Departmana ait kullanıcıları kontrol et
    $check = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE department = ?");
    $check->execute([$dept_code]);
    $user_count = $check->fetch()['count'];

    // Departmana ait kategorileri kontrol et
    $check = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE department = ?");
    $check->execute([$dept_code]);
    $category_count = $check->fetch()['count'];

    if ($user_count > 0) {
        $error = "Bu departmanda $user_count kullanıcı bulunmaktadır. Önce kullanıcıları başka departmana taşıyın.";
    } elseif ($category_count > 0) {
        $error = "Bu departmanda $category_count kategori bulunmaktadır. Önce kategorileri silin veya taşıyın.";
    } else {
        $pdo->prepare("DELETE FROM departments WHERE code = ?")->execute([$dept_code]);
        $success = 'Departman başarıyla silindi.';
    }
}

// Kullanıcı ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department = $_POST['department'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurun.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'Bu e-posta adresi zaten kullanılıyor.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role, $department]);
            $success = 'Kullanıcı başarıyla eklendi.';
        }
    }
}

// Kullanıcı düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $department = $_POST['department'];
    $password = $_POST['password'];

    if (empty($name) || empty($email)) {
        $error = 'Ad ve e-posta alanları zorunludur.';
    } else {
        // E-posta kontrolü
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);

        if ($check->fetch()) {
            $error = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.';
        } else {
            if (!empty($password)) {
                // Şifre değiştirilecek
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, department = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $department, $hashed, $user_id]);
            } else {
                // Şifre değiştirilmeyecek
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, department = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $department, $user_id]);
            }
            $success = 'Kullanıcı başarıyla güncellendi.';
        }
    }
}

// Kullanıcı silme
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    if ($user_id != getUserId()) {
        $check = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE user_id = ?");
        $check->execute([$user_id]);
        $ticket_count = $check->fetch()['count'];

        if ($ticket_count > 0) {
            $error = "Bu kullanıcının $ticket_count adet ticketı bulunmaktadır. Önce ticketları silmelisiniz.";
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $success = 'Kullanıcı başarıyla silindi.';
        }
    } else {
        $error = 'Kendi hesabınızı silemezsiniz.';
    }
}

// Kategori ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $department = $_POST['department'];

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, department) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $department]);
        $success = 'Kategori eklendi.';
    }
}

// Kategori silme
if (isset($_GET['delete_category'])) {
    $cat_id = $_GET['delete_category'];
    $check = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE category_id = ?");
    $check->execute([$cat_id]);
    $ticket_count = $check->fetch()['count'];

    if ($ticket_count > 0) {
        $error = "Bu kategoride $ticket_count adet ticket bulunmaktadır. Önce ticketları başka kategoriye taşıyın.";
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cat_id]);
        $success = 'Kategori başarıyla silindi.';
    }
}

// SSS ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faq'])) {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $category_id = $_POST['category_id'] ?? null;

    if (!empty($question) && !empty($answer)) {
        $stmt = $pdo->prepare("INSERT INTO faq (question, answer, category_id) VALUES (?, ?, ?)");
        $stmt->execute([$question, $answer, $category_id]);
        $success = 'SSS eklendi.';
    }
}

// SSS silme
if (isset($_GET['delete_faq'])) {
    $pdo->prepare("DELETE FROM faq WHERE id = ?")->execute([$_GET['delete_faq']]);
    $success = 'SSS silindi.';
}

// Kullanıcı düzenleme modali için
$edit_user_data = null;
if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit_user']]);
    $edit_user_data = $stmt->fetch();
}

// Verileri çek
$users = $pdo->query("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department = d.code ORDER BY u.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT c.*, d.name as department_name FROM categories c LEFT JOIN departments d ON c.department = d.code ORDER BY d.name, c.name")->fetchAll();
$departments_list = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$faq_list = $pdo->query("SELECT f.*, c.name as category_name FROM faq f LEFT JOIN categories c ON f.category_id = c.id ORDER BY f.created_at DESC")->fetchAll();

// İstatistikler
$stats_users = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch();
$stats_tickets = $pdo->query("SELECT COUNT(*) as total FROM tickets")->fetch();
$stats_departments = $pdo->query("SELECT COUNT(*) as total FROM departments WHERE is_active = 1")->fetch();
$stats_categories = $pdo->query("SELECT COUNT(*) as total FROM categories")->fetch();
$stats_faq = $pdo->query("SELECT COUNT(*) as total FROM faq")->fetch();

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-cog"></i> Yönetim Paneli</h2>
            <p class="text-muted">Sistem ayarları ve yönetim işlemleri</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-2"></i>
                    <h3><?php echo $stats_users['total']; ?></h3>
                    <p class="text-muted">Kullanıcı</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-ticket-alt fa-3x text-success mb-2"></i>
                    <h3><?php echo $stats_tickets['total']; ?></h3>
                    <p class="text-muted">Ticket</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-building fa-3x text-info mb-2"></i>
                    <h3><?php echo $stats_departments['total']; ?></h3>
                    <p class="text-muted">Departman</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-folder fa-3x text-warning mb-2"></i>
                    <h3><?php echo $stats_categories['total']; ?></h3>
                    <p class="text-muted">Kategori</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-question-circle fa-3x text-danger mb-2"></i>
                    <h3><?php echo $stats_faq['total']; ?></h3>
                    <p class="text-muted">SSS</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Menü -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'users' ? 'active' : ''; ?>" href="?tab=users">
                <i class="fas fa-users"></i> Kullanıcılar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'departments' ? 'active' : ''; ?>" href="?tab=departments">
                <i class="fas fa-building"></i> Departmanlar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'categories' ? 'active' : ''; ?>" href="?tab=categories">
                <i class="fas fa-folder"></i> Kategoriler
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'faq' ? 'active' : ''; ?>" href="?tab=faq">
                <i class="fas fa-question-circle"></i> SSS
            </a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <!-- Kullanıcılar Tab -->
        <?php if ($tab === 'users'): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-plus"></i> Yeni Kullanıcı Ekle</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Ad Soyad</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>E-posta</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Şifre</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Rol</label>
                                    <select name="role" class="form-control" required>
                                        <option value="user">Kullanıcı</option>
                                        <option value="manager">Yönetici</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Departman</label>
                                    <select name="department" class="form-control" required>
                                        <?php foreach ($departments_list as $dept): ?>
                                            <option value="<?php echo $dept['code']; ?>"><?php echo sanitize($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_user" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Kullanıcı Listesi</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>E-posta</th>
                                        <th>Rol</th>
                                        <th>Departman</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo sanitize($user['name']); ?></td>
                                            <td><?php echo sanitize($user['email']); ?></td>
                                            <td><?php echo getRoleBadge($user['role']); ?></td>
                                            <td><?php echo $user['department_name'] ? sanitize($user['department_name']) : '-'; ?></td>
                                            <td>
                                                <a href="?tab=users&edit_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != getUserId()): ?>
                                                    <a href="?tab=users&delete_user=<?php echo $user['id']; ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kullanıcı Düzenleme Modali -->
            <?php if ($edit_user_data): ?>
                <div class="modal fade show" id="editUserModal" style="display:block; background: rgba(0,0,0,0.5);">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Kullanıcı Düzenle</h5>
                                <a href="?tab=users" class="close">&times;</a>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="user_id" value="<?php echo $edit_user_data['id']; ?>">
                                    <div class="form-group">
                                        <label>Ad Soyad</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo sanitize($edit_user_data['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>E-posta</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($edit_user_data['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Şifre (Boş bırakılırsa değişmez)</label>
                                        <input type="password" name="password" class="form-control" placeholder="Yeni şifre (opsiyonel)">
                                    </div>
                                    <div class="form-group">
                                        <label>Rol</label>
                                        <select name="role" class="form-control" required>
                                            <option value="user" <?php echo $edit_user_data['role'] === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                                            <option value="manager" <?php echo $edit_user_data['role'] === 'manager' ? 'selected' : ''; ?>>Yönetici</option>
                                            <option value="admin" <?php echo $edit_user_data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Departman</label>
                                        <select name="department" class="form-control" required>
                                            <?php foreach ($departments_list as $dept): ?>
                                                <option value="<?php echo $dept['code']; ?>" <?php echo $edit_user_data['department'] === $dept['code'] ? 'selected' : ''; ?>>
                                                    <?php echo sanitize($dept['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="?tab=users" class="btn btn-secondary">İptal</a>
                                    <button type="submit" name="edit_user" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Departmanlar Tab -->
        <?php if ($tab === 'departments'): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-building"></i> Yeni Departman Ekle</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Departman Kodu *</label>
                                    <input type="text" name="code" class="form-control" placeholder="ornek: teknik_destek" required>
                                    <small class="form-text text-muted">Sadece küçük harf ve alt çizgi kullanın</small>
                                </div>
                                <div class="form-group">
                                    <label>Departman Adı *</label>
                                    <input type="text" name="name" class="form-control" placeholder="Teknik Destek" required>
                                </div>
                                <div class="form-group">
                                    <label>Açıklama</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Departman açıklaması"></textarea>
                                </div>
                                <button type="submit" name="add_department" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Departman Listesi</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Departman Kodu</th>
                                        <th>Departman Adı</th>
                                        <th>Açıklama</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments_list as $dept): ?>
                                        <tr>
                                            <td><code><?php echo sanitize($dept['code']); ?></code></td>
                                            <td><?php echo sanitize($dept['name']); ?></td>
                                            <td><?php echo sanitize($dept['description']); ?></td>
                                            <td>
                                                <a href="?tab=departments&delete_department=<?php echo $dept['code']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Bu departmanı silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Kategoriler Tab -->
        <?php if ($tab === 'categories'): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-folder-plus"></i> Yeni Kategori Ekle</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Kategori Adı</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Departman</label>
                                    <select name="department" class="form-control" required>
                                        <?php foreach ($departments_list as $dept): ?>
                                            <option value="<?php echo $dept['code']; ?>"><?php echo sanitize($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Açıklama</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" name="add_category" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Kategori Listesi</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Kategori Adı</th>
                                        <th>Departman</th>
                                        <th>Açıklama</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><?php echo sanitize($cat['name']); ?></td>
                                            <td><?php echo $cat['department_name'] ? sanitize($cat['department_name']) : '-'; ?></td>
                                            <td><?php echo sanitize($cat['description']); ?></td>
                                            <td>
                                                <a href="?tab=categories&delete_category=<?php echo $cat['id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SSS Tab -->
        <?php if ($tab === 'faq'): ?>
            <div class="row">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-plus"></i> Yeni SSS Ekle</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Soru</label>
                                    <input type="text" name="question" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Cevap</label>
                                    <textarea name="answer" class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" name="add_faq" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> SSS Listesi</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Soru</th>
                                        <th>Kategori</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faq_list as $faq): ?>
                                        <tr>
                                            <td><?php echo sanitize($faq['question']); ?></td>
                                            <td><?php echo $faq['category_name'] ? sanitize($faq['category_name']) : '-'; ?></td>
                                            <td>
                                                <a href="?tab=faq&delete_faq=<?php echo $faq['id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>