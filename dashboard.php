<?php
require_once 'config.php';
requireLogin();

// İstatistikler
$userId = getUserId();
$role = getUserRole();

// Kullanıcıya göre ticket sorgusu - HERKES TÜM İSTATİSTİKLERİ GÖREBİLİR
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 'assigned' OR status = 'in_progress' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM tickets");
$stats = $stmt->fetch();

// Son ticketlar - HERKES TÜM TICKETLARı GÖREBİLİR
$userDept = getUserDepartment();

$stmt = $pdo->query("SELECT t.*, u.name as user_name, u.department as user_department, 
                     a.name as assigned_name, c.department as category_department
                     FROM tickets t 
                     LEFT JOIN users u ON t.user_id = u.id 
                     LEFT JOIN users a ON t.assigned_to = a.id 
                     LEFT JOIN categories c ON t.category_id = c.id
                     ORDER BY t.created_at DESC LIMIT 10");
$recent_tickets = $stmt->fetchAll();

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            <p class="text-muted">Hoş geldiniz, <?php echo sanitize($_SESSION['user_name']); ?>!</p>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Toplam Ticket</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card stat-open">
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['open']; ?></h3>
                    <p>Bekleyen</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card stat-active">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['active']; ?></h3>
                    <p>Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card stat-waiting">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['waiting']; ?></h3>
                    <p>Askıda</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card stat-completed">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p>Tamamlanan</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card stat-cancelled">
                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['cancelled']; ?></h3>
                    <p>İptal</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Ticketlar -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Son Ticketlar</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ticket No</th>
                                    <th>Konu</th>
                                    <?php if (isManager()): ?>
                                        <th>Kullanıcı</th>
                                    <?php endif; ?>
                                    <th>Durum</th>
                                    <th>Öncelik</th>
                                    <?php if (isAdmin()): ?>
                                        <th>Atanan</th>
                                    <?php endif; ?>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_tickets)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Henüz ticket bulunmuyor.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_tickets as $ticket): ?>
                                        <?php $can_view_detail = canViewTicketDetail($ticket, $userId, $role, $userDept); ?>
                                        <tr <?php echo !$can_view_detail ? 'style="opacity: 0.6;"' : ''; ?>>
                                            <td><strong><?php echo sanitize($ticket['ticket_number']); ?></strong></td>
                                            <td><?php echo sanitize($ticket['subject']); ?></td>
                                            <?php if (isManager()): ?>
                                                <td><?php echo sanitize($ticket['user_name']); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                            <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                            <?php if (isAdmin()): ?>
                                                <td><?php echo $ticket['assigned_name'] ? sanitize($ticket['assigned_name']) : '<em>Atanmadı</em>'; ?></td>
                                            <?php endif; ?>
                                            <td><?php echo timeAgo($ticket['created_at']); ?></td>
                                            <td>
                                                <?php if ($can_view_detail): ?>
                                                    <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Detay görüntüleme yetkiniz yok">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="tickets.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Tüm Ticketları Görüntüle
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>