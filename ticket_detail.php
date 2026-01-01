<?php
require_once 'config.php';
requireLogin();

$ticket_id = $_GET['id'] ?? 0;
$userId = getUserId();
$role = getUserRole();
$userDept = getUserDepartment();

// Ticket bilgilerini çek
$stmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.email as user_email, u.department as user_department,
                       a.name as assigned_name, c.name as category_name, c.department as category_department
                       FROM tickets t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       LEFT JOIN users a ON t.assigned_to = a.id 
                       LEFT JOIN categories c ON t.category_id = c.id 
                       WHERE t.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect('tickets.php');
}

// Detay görüntüleme yetkisi kontrolü
if (!canViewTicketDetail($ticket, $userId, $role, $userDept)) {
    $_SESSION['error_message'] = 'Bu ticketın detaylarını görüntüleme yetkiniz yok.';
    redirect('tickets.php');
}

$error = '';
$success = '';

// Mesaj silme
if (isset($_GET['delete_message']) && isLoggedIn()) {
    $message_id = $_GET['delete_message'];

    // Mesaj sahibi mi kontrol et
    $stmt = $pdo->prepare("SELECT user_id FROM ticket_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();

    if ($message && ($message['user_id'] == $userId || isAdmin())) {
        $pdo->prepare("DELETE FROM ticket_messages WHERE id = ?")->execute([$message_id]);

        // Log kaydet
        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, old_value) VALUES (?, ?, 'message_deleted', ?)");
        $stmt->execute([$ticket_id, $userId, "Mesaj silindi (ID: $message_id)"]);

        $success = 'Mesaj silindi.';
        header("Location: ticket_detail.php?id=$ticket_id");
        exit;
    }
}

// Mesaj düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_message'])) {
    $message_id = $_POST['message_id'];
    $new_message = trim($_POST['message']);

    // Mesaj sahibi mi kontrol et
    $stmt = $pdo->prepare("SELECT user_id, message FROM ticket_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $old_message_data = $stmt->fetch();

    if ($old_message_data && ($old_message_data['user_id'] == $userId || isAdmin())) {
        $stmt = $pdo->prepare("UPDATE ticket_messages SET message = ? WHERE id = ?");
        $stmt->execute([$new_message, $message_id]);

        // Log kaydet
        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, old_value, new_value) VALUES (?, ?, 'message_edited', ?, ?)");
        $stmt->execute([$ticket_id, $userId, substr($old_message_data['message'], 0, 100), substr($new_message, 0, 100)]);

        $success = 'Mesaj güncellendi.';
        header("Location: ticket_detail.php?id=$ticket_id");
        exit;
    }
}

// Ticket silme
if (isset($_GET['delete']) && $_GET['delete'] == 'confirm') {
    if (canDeleteTicket($ticket, $userId, $role, $userDept)) {
        // Dosyaları sil
        if ($ticket['attachment'] && file_exists('uploads/' . $ticket['attachment'])) {
            unlink('uploads/' . $ticket['attachment']);
        }

        // Ekleri sil
        $stmt = $pdo->prepare("SELECT filename FROM ticket_attachments WHERE ticket_id = ?");
        $stmt->execute([$ticket_id]);
        while ($att = $stmt->fetch()) {
            if (file_exists('uploads/' . $att['filename'])) {
                unlink('uploads/' . $att['filename']);
            }
        }

        // Log kaydet (silmeden önce)
        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) VALUES (?, ?, 'ticket_deleted', ?)");
        $stmt->execute([$ticket_id, $userId, "Ticket tamamen silindi: " . $ticket['ticket_number']]);

        $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$ticket_id]);
        redirect('tickets.php?deleted=1');
    } else {
        $error = 'Bu ticketı silme yetkiniz yok.';
    }
}

// Mesaj gönder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $error = 'Lütfen mesaj yazın.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $userId, $message]);

        $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

        // Log kaydet
        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) VALUES (?, ?, 'message_added', ?)");
        $stmt->execute([$ticket_id, $userId, "Yeni mesaj eklendi"]);

        // Bildirim gönder
        if ($role === 'admin') {
            // Admin mesaj attı, departman kullanıcılarına bildir
            notifyDepartmentUsers(
                $ticket_id,
                $ticket['category_department'],
                'new_message',
                "#{$ticket['ticket_number']} numaralı ticketınıza admin yanıt verdi",
                $userId
            );
        } else {
            // Kullanıcı mesaj attı, adminlere bildir
            notifyAdmins(
                $ticket_id,
                'new_message',
                "#{$ticket['ticket_number']} numaralı tickete yeni mesaj",
                $userId
            );
        }

        $success = 'Mesaj gönderildi.';
        header("Location: ticket_detail.php?id=$ticket_id");
        exit;
    }
}

// Kategori güncelle (sadece admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category']) && isAdmin()) {
    $new_category = $_POST['category_id'] ?? null;
    $old_category = $ticket['category_id'];

    // Kategori adlarını al
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$old_category]);
    $old_cat_name = $stmt->fetch()['name'] ?? 'Yok';

    $stmt->execute([$new_category]);
    $new_cat_name = $stmt->fetch()['name'] ?? 'Yok';

    $stmt = $pdo->prepare("UPDATE tickets SET category_id = ? WHERE id = ?");
    $stmt->execute([$new_category, $ticket_id]);

    // Log kaydet
    $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, old_value, new_value) 
                           VALUES (?, ?, 'category_changed', ?, ?)");
    $stmt->execute([$ticket_id, $userId, $old_cat_name, $new_cat_name]);

    $success = 'Kategori güncellendi.';
    header("Location: ticket_detail.php?id=$ticket_id");
    exit;
}

// Durum güncelle (yönetici ve admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && isManager()) {
    $new_status = $_POST['status'];
    $old_status = $ticket['status'];

    // Yöneticiler sadece kendi departmanlarının ticketlarında durum değiştirebilir
    if ($role === 'admin' || $ticket['category_department'] === $userDept) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $ticket_id]);

        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, old_value, new_value) 
                               VALUES (?, ?, 'status_changed', ?, ?)");
        $stmt->execute([$ticket_id, $userId, $old_status, $new_status]);

        $success = 'Durum güncellendi.';
        header("Location: ticket_detail.php?id=$ticket_id");
        exit;
    } else {
        $error = 'Bu ticketın durumunu değiştirme yetkiniz yok.';
    }
}

// Atama yap (sadece admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket']) && isAdmin()) {
    $assign_to = $_POST['assign_to'] ?? null;

    $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = 'assigned' WHERE id = ?");
    $stmt->execute([$assign_to, $ticket_id]);

    $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) 
                           VALUES (?, ?, 'assigned', ?)");
    $stmt->execute([$ticket_id, $userId, $assign_to]);

    $success = 'Ticket atandı.';
    header("Location: ticket_detail.php?id=$ticket_id");
    exit;
}

// Mesajları çek
$stmt = $pdo->prepare("SELECT m.*, u.name as user_name, u.role, u.department
                       FROM ticket_messages m 
                       LEFT JOIN users u ON m.user_id = u.id 
                       WHERE m.ticket_id = ? 
                       ORDER BY m.created_at ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll();

// Ekleri çek
$stmt = $pdo->prepare("SELECT a.*, u.name as uploaded_by_name 
                       FROM ticket_attachments a
                       LEFT JOIN users u ON a.uploaded_by = u.id
                       WHERE a.ticket_id = ?
                       ORDER BY a.created_at DESC");
$stmt->execute([$ticket_id]);
$attachments = $stmt->fetchAll();

// Log kayıtlarını çek (sadece admin)
$logs = [];
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT h.*, u.name as user_name
                           FROM ticket_history h
                           LEFT JOIN users u ON h.user_id = u.id
                           WHERE h.ticket_id = ?
                           ORDER BY h.created_at DESC");
    $stmt->execute([$ticket_id]);
    $logs = $stmt->fetchAll();
}

// Kategoriler listesi
$categories = $pdo->query("SELECT c.*, d.name as department_name FROM categories c LEFT JOIN departments d ON c.department = d.code ORDER BY d.name, c.name")->fetchAll();

// Admin kullanıcıları çek (atama için)
if (isAdmin()) {
    $admins = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY name")->fetchAll();
}

// Silme yetkisi kontrolü
$can_delete = canDeleteTicket($ticket, $userId, $role, $userDept);

// Durum değiştirme yetkisi kontrolü
$can_change_status = isManager() && ($role === 'admin' || $ticket['category_department'] === $userDept);

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8">
            <!-- Ticket Bilgileri -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-ticket-alt"></i>
                        <?php echo sanitize($ticket['ticket_number']); ?>
                    </h5>
                    <div>
                        <?php echo getStatusBadge($ticket['status']); ?>
                        <?php if ($ticket['requires_approval']): ?>
                            <span class="badge badge-warning">Onay Gerekli</span>
                        <?php endif; ?>
                        <?php if ($can_delete): ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteTicket()">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <h4><?php echo sanitize($ticket['subject']); ?></h4>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong><i class="fas fa-user"></i> Oluşturan:</strong> <?php echo sanitize($ticket['user_name']); ?></p>
                            <p class="mb-1"><strong><i class="fas fa-envelope"></i> E-posta:</strong> <?php echo sanitize($ticket['user_email']); ?></p>
                            <p class="mb-1"><strong><i class="fas fa-building"></i> Departman:</strong> <?php echo getDepartmentBadge($ticket['user_department']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong><i class="fas fa-calendar"></i> Oluşturma:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></p>
                            <p class="mb-1"><strong><i class="fas fa-clock"></i> Güncelleme:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?></p>
                        </div>
                    </div>
                    <div class="alert alert-light">
                        <strong><i class="fas fa-align-left"></i> Açıklama:</strong><br>
                        <?php echo nl2br(sanitize($ticket['description'])); ?>
                    </div>

                    <?php if ($ticket['attachment']): ?>
                        <div class="alert alert-info">
                            <strong><i class="fas fa-paperclip"></i> Ek Dosya:</strong><br>
                            <?php
                            $file_path = 'uploads/' . $ticket['attachment'];
                            $file_ext = strtolower(pathinfo($ticket['attachment'], PATHINFO_EXTENSION));
                            $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                            ?>

                            <?php if (in_array($file_ext, $image_extensions)): ?>
                                <a href="<?php echo $file_path; ?>" target="_blank">
                                    <img src="<?php echo $file_path; ?>" alt="Ek Dosya" class="img-fluid mt-2" style="max-width: 500px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $file_path; ?>" target="_blank" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-download"></i> <?php echo sanitize($ticket['attachment']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dosya Ekleri -->
            <?php if (!empty($attachments)): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-paperclip"></i> Ekli Dosyalar (<?php echo count($attachments); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($attachments as $att): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <?php if (in_array($att['file_type'], ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <img src="uploads/<?php echo $att['filename']; ?>" class="card-img-top" alt="<?php echo sanitize($att['original_name']); ?>" style="height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="card-body text-center" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <p class="card-text small mb-1"><?php echo sanitize($att['original_name']); ?></p>
                                            <small class="text-muted d-block mb-2">
                                                <i class="fas fa-user"></i> <?php echo sanitize($att['uploaded_by_name']); ?><br>
                                                <i class="fas fa-clock"></i> <?php echo timeAgo($att['created_at']); ?>
                                            </small>
                                            <a href="uploads/<?php echo $att['filename']; ?>" target="_blank" class="btn btn-sm btn-primary btn-block">
                                                <i class="fas fa-download"></i> İndir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mesajlar -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="fas fa-comments"></i> Mesajlar (<?php echo count($messages); ?>)</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-item mb-3 p-3" style="background: <?php echo $msg['role'] === 'user' ? '#f8f9fa' : '#e3f2fd'; ?>; border-radius: 8px;">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong>
                                        <i class="fas fa-user-circle"></i> <?php echo sanitize($msg['user_name']); ?>
                                        <?php echo getRoleBadge($msg['role']); ?>
                                        <?php echo getDepartmentBadge($msg['department']); ?>
                                    </strong>
                                </div>
                                <div>
                                    <small class="text-muted"><?php echo timeAgo($msg['created_at']); ?></small>
                                    <?php if ($msg['user_id'] == $userId || isAdmin()): ?>
                                        <button class="btn btn-sm btn-warning ml-2" onclick="editMessage(<?php echo $msg['id']; ?>)" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="ticket_detail.php?id=<?php echo $ticket_id; ?>&delete_message=<?php echo $msg['id']; ?>"
                                            class="btn btn-sm btn-danger ml-1"
                                            onclick="return confirm('Bu mesajı silmek istediğinize emin misiniz?')"
                                            title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="message-<?php echo $msg['id']; ?>-text">
                                <?php echo nl2br(sanitize($msg['message'])); ?>
                            </div>
                            <div id="message-<?php echo $msg['id']; ?>-edit" style="display: none;">
                                <form method="POST" action="">
                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                    <textarea name="message" class="form-control mb-2" rows="3"><?php echo sanitize($msg['message']); ?></textarea>
                                    <button type="submit" name="edit_message" class="btn btn-sm btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEdit(<?php echo $msg['id']; ?>)">
                                        İptal
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Yeni Mesaj Formu -->
            <?php if ($ticket['status'] !== 'completed' && $ticket['status'] !== 'cancelled'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-reply"></i> Yanıt Yaz</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <textarea class="form-control" name="message" rows="4" placeholder="Mesajınızı yazın..." required></textarea>
                            </div>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Mesaj Gönder
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Admin Log Paneli -->
            <?php if (isAdmin()): ?>
                <div class="card mt-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="fas fa-history"></i> İşlem Geçmişi (Log) - Sadece Admin Görür</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="180">Tarih & Saat</th>
                                        <th width="150">Kullanıcı</th>
                                        <th width="150">İşlem</th>
                                        <th>Eski Değer</th>
                                        <th>Yeni Değer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Henüz log kaydı yok</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $action_labels = [
                                            'created' => '<span class="badge badge-success">Oluşturuldu</span>',
                                            'created_pending' => '<span class="badge badge-warning">Onay Bekliyor</span>',
                                            'status_changed' => '<span class="badge badge-info">Durum Değişti</span>',
                                            'category_changed' => '<span class="badge badge-primary">Kategori Değişti</span>',
                                            'assigned' => '<span class="badge badge-secondary">Atandı</span>',
                                            'message_added' => '<span class="badge badge-success">Mesaj Eklendi</span>',
                                            'message_edited' => '<span class="badge badge-warning">Mesaj Düzenlendi</span>',
                                            'message_deleted' => '<span class="badge badge-danger">Mesaj Silindi</span>',
                                            'ticket_deleted' => '<span class="badge badge-danger">Ticket Silindi</span>',
                                            'approved' => '<span class="badge badge-success">Onaylandı</span>',
                                            'rejected' => '<span class="badge badge-danger">Reddedildi</span>'
                                        ];
                                        foreach ($logs as $log):
                                        ?>
                                            <tr>
                                                <td><small><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                                <td><small><?php echo sanitize($log['user_name']); ?></small></td>
                                                <td>
                                                    <?php echo $action_labels[$log['action']] ?? '<span class="badge badge-secondary">' . $log['action'] . '</span>'; ?>
                                                </td>
                                                <td><small><?php echo $log['old_value'] ? sanitize($log['old_value']) : '-'; ?></small></td>
                                                <td><small><?php echo $log['new_value'] ? sanitize($log['new_value']) : '-'; ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sağ Panel -->
        <div class="col-md-4">
            <!-- Ticket Detayları -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-info-circle"></i> Ticket Detayları</h6>
                </div>
                <div class="card-body">
                    <p><strong>Öncelik:</strong><br><?php echo getPriorityBadge($ticket['priority']); ?></p>
                    <p><strong>Kategori:</strong><br>
                        <?php if ($ticket['category_name']): ?>
                            <span class="badge badge-secondary"><?php echo sanitize($ticket['category_name']); ?></span><br>
                            <small><?php echo getDepartmentBadge($ticket['category_department']); ?></small>
                        <?php else: ?>
                            <em>Belirtilmemiş</em>
                        <?php endif; ?>
                    </p>
                    <?php if (isAdmin()): ?>
                        <p><strong>Atanan Kişi:</strong><br>
                            <?php echo $ticket['assigned_name'] ? sanitize($ticket['assigned_name']) : '<em>Henüz atanmadı</em>'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Yönetici/Admin İşlemleri -->
            <?php if ($can_change_status || isAdmin()): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-cog"></i> Yönetim İşlemleri</h6>
                    </div>
                    <div class="card-body">
                        <!-- Kategori Değiştir (Sadece Admin) -->
                        <?php if (isAdmin()): ?>
                            <form method="POST" action="" class="mb-3">
                                <label><strong>Kategori Değiştir:</strong></label>
                                <select class="form-control mb-2" name="category_id">
                                    <option value="">Kategori Seçiniz</option>
                                    <?php
                                    $current_dept = '';
                                    foreach ($categories as $cat):
                                        if ($current_dept != $cat['department_name']) {
                                            if ($current_dept != '') echo '</optgroup>';
                                            echo '<optgroup label="' . sanitize($cat['department_name']) . '">';
                                            $current_dept = $cat['department_name'];
                                        }
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $ticket['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo sanitize($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($current_dept != '') echo '</optgroup>'; ?>
                                </select>
                                <button type="submit" name="update_category" class="btn btn-sm btn-info btn-block">
                                    <i class="fas fa-folder"></i> Kategoriyi Güncelle
                                </button>
                            </form>

                            <hr>
                        <?php endif; ?>

                        <!-- Durum Değiştir (Yönetici + Admin) -->
                        <?php if ($can_change_status): ?>
                            <form method="POST" action="" class="mb-3">
                                <label><strong>Durum Değiştir:</strong></label>
                                <select class="form-control mb-2" name="status">
                                    <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Açık</option>
                                    <option value="assigned" <?php echo $ticket['status'] === 'assigned' ? 'selected' : ''; ?>>Atandı</option>
                                    <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>İşlemde</option>
                                    <option value="waiting" <?php echo $ticket['status'] === 'waiting' ? 'selected' : ''; ?>>Beklemede</option>
                                    <option value="completed" <?php echo $ticket['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                                    <option value="cancelled" <?php echo $ticket['status'] === 'cancelled' ? 'selected' : ''; ?>>İptal</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary btn-block">
                                    <i class="fas fa-save"></i> Durumu Güncelle
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Atama Yap (Sadece Admin) -->
                        <?php if (isAdmin()): ?>
                            <hr>
                            <form method="POST" action="">
                                <label><strong>Admin Ata:</strong></label>
                                <select class="form-control mb-2" name="assign_to">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>" <?php echo $ticket['assigned_to'] == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo sanitize($admin['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_ticket" class="btn btn-sm btn-warning btn-block">
                                    <i class="fas fa-user-tag"></i> Ata
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- İşlemler -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-tools"></i> İşlemler</h6>
                </div>
                <div class="card-body">
                    <a href="tickets.php" class="btn btn-secondary btn-block mb-2">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function deleteTicket() {
        if (confirm('Bu ticketı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
            window.location.href = 'ticket_detail.php?id=<?php echo $ticket_id; ?>&delete=confirm';
        }
    }

    function editMessage(messageId) {
        document.getElementById('message-' + messageId + '-text').style.display = 'none';
        document.getElementById('message-' + messageId + '-edit').style.display = 'block';
    }

    function cancelEdit(messageId) {
        document.getElementById('message-' + messageId + '-text').style.display = 'block';
        document.getElementById('message-' + messageId + '-edit').style.display = 'none';
    }
</script>

<?php include 'footer.php'; ?>