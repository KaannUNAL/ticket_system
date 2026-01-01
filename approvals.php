<?php
require_once 'config.php';
requireManager();

$userId = getUserId();
$userRole = getUserRole();
$userDept = getUserDepartment();

$error = '';
$success = '';

// Onay/Red işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $approval_id = $_POST['approval_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $ticket_id = $_POST['ticket_id'] ?? 0;

    if ($action === 'approve') {
        // Onay
        $stmt = $pdo->prepare("UPDATE ticket_approvals SET status = 'approved', approved_by = ? WHERE id = ?");
        $stmt->execute([$userId, $approval_id]);

        // Ticketı aktif et
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'open', approved_by = ? WHERE id = ?");
        $stmt->execute([$userId, $ticket_id]);

        // İlk mesajı ekle
        $stmt = $pdo->prepare("SELECT description, user_id FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();

        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $ticket['user_id'], $ticket['description']]);

        // Geçmişe kaydet
        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) VALUES (?, ?, 'approved', ?)");
        $stmt->execute([$ticket_id, $userId, 'Yönetici tarafından onaylandı']);

        $success = 'Ticket onaylandı ve kullanıcıya açıldı.';
    } elseif ($action === 'reject') {
        // Red
        $stmt = $pdo->prepare("UPDATE ticket_approvals SET status = 'rejected', approved_by = ? WHERE id = ?");
        $stmt->execute([$userId, $approval_id]);

        // Ticketı iptal et
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$ticket_id]);

        // Geçmişe kaydet
        $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) VALUES (?, ?, 'rejected', ?)");
        $stmt->execute([$ticket_id, $userId, 'Yönetici tarafından reddedildi']);

        $error = 'Ticket reddedildi.';
    }
}

// Onay bekleyen ticketları çek
if ($userRole === 'admin') {
    // Admin tüm onay bekleyenleri görebilir
    $stmt = $pdo->query("SELECT ta.*, t.ticket_number, t.subject, t.description, t.priority, t.created_at,
                         u.name as user_name, u.email as user_email, u.department as user_department,
                         c.name as category_name, c.department as category_department
                         FROM ticket_approvals ta
                         LEFT JOIN tickets t ON ta.ticket_id = t.id
                         LEFT JOIN users u ON ta.user_id = u.id
                         LEFT JOIN categories c ON ta.category_id = c.id
                         WHERE ta.status = 'pending'
                         ORDER BY ta.created_at DESC");
} else {
    // Yönetici sadece kendi departmanına ait onay bekleyenleri görebilir
    $stmt = $pdo->prepare("SELECT ta.*, t.ticket_number, t.subject, t.description, t.priority, t.created_at,
                           u.name as user_name, u.email as user_email, u.department as user_department,
                           c.name as category_name, c.department as category_department
                           FROM ticket_approvals ta
                           LEFT JOIN tickets t ON ta.ticket_id = t.id
                           LEFT JOIN users u ON ta.user_id = u.id
                           LEFT JOIN categories c ON ta.category_id = c.id
                           WHERE ta.status = 'pending' AND c.department = ?
                           ORDER BY ta.created_at DESC");
    $stmt->execute([$userDept]);
}

$pending_approvals = $stmt->fetchAll();

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-check-circle"></i> Onay Bekleyen Ticketlar</h2>
            <p class="text-muted">Departman dışı kategorilerde açılmak istenen ticketlar</p>
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

    <div class="row">
        <div class="col-12">
            <?php if (empty($pending_approvals)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                        <h4>Onay bekleyen ticket bulunmuyor</h4>
                        <p class="text-muted">Tüm ticketlar onaylandı veya henüz onay talebi yok.</p>
                        <a href="tickets.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Ticketlara Dön
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <?php foreach ($pending_approvals as $approval): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-warning">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0">
                                        <i class="fas fa-ticket-alt"></i> <?php echo sanitize($approval['ticket_number']); ?>
                                        - <?php echo sanitize($approval['subject']); ?>
                                    </h5>
                                </div>
                                <div class="col-md-4 text-right">
                                    <?php echo getPriorityBadge($approval['priority']); ?>
                                    <span class="badge badge-info ml-2">Onay Bekliyor</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-user"></i> Talep Eden:</strong> <?php echo sanitize($approval['user_name']); ?></p>
                                    <p class="mb-1"><strong><i class="fas fa-envelope"></i> E-posta:</strong> <?php echo sanitize($approval['user_email']); ?></p>
                                    <p class="mb-1"><strong><i class="fas fa-building"></i> Departman:</strong> <?php echo getDepartmentBadge($approval['user_department']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-folder"></i> İstenen Kategori:</strong>
                                        <span class="badge badge-primary"><?php echo sanitize($approval['category_name']); ?></span>
                                    </p>
                                    <p class="mb-1"><strong><i class="fas fa-building"></i> Kategori Departmanı:</strong>
                                        <?php echo getDepartmentBadge($approval['category_department']); ?>
                                    </p>
                                    <p class="mb-1"><strong><i class="fas fa-calendar"></i> Talep Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($approval['created_at'])); ?></p>
                                </div>
                            </div>

                            <div class="alert alert-light">
                                <strong><i class="fas fa-align-left"></i> Açıklama:</strong><br>
                                <?php echo nl2br(sanitize($approval['description'])); ?>
                            </div>

                            <div class="alert alert-warning">
                                <strong><i class="fas fa-exclamation-triangle"></i> Onay Nedeni:</strong><br>
                                <?php echo sanitize($approval['reason']); ?>
                            </div>

                            <hr>

                            <div class="text-center">
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                    <input type="hidden" name="ticket_id" value="<?php echo $approval['ticket_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Bu ticketı onaylamak istediğinize emin misiniz?')">
                                        <i class="fas fa-check"></i> Onayla ve Aç
                                    </button>
                                </form>

                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                    <input type="hidden" name="ticket_id" value="<?php echo $approval['ticket_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Bu ticketı reddetmek istediğinize emin misiniz? Ticket iptal edilecektir.')">
                                        <i class="fas fa-times"></i> Reddet
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>