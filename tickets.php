<?php
require_once 'config.php';
requireLogin();

$userId = getUserId();
$role = getUserRole();
$userDept = getUserDepartment();

// Filtreleme
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Sorgu oluştur - HERKES TÜM TICKETLARı GÖREBİLİR
$where = [];
$params = [];

if ($status_filter) {
    $where[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where[] = "t.priority = ?";
    $params[] = $priority_filter;
}

if ($search) {
    $where[] = "(t.ticket_number LIKE ? OR t.subject LIKE ? OR t.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.department as user_department, 
                       a.name as assigned_name, c.name as category_name, c.department as category_department
                       FROM tickets t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       LEFT JOIN users a ON t.assigned_to = a.id 
                       LEFT JOIN categories c ON t.category_id = c.id 
                       $where_clause 
                       ORDER BY t.created_at DESC");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include 'header.php';
?>

<div class="container-fluid mt-4">
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> Ticket başarıyla silindi.
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-ticket-alt"></i> Ticketlar</h2>
            <p class="text-muted">
                <?php if ($role === 'admin'): ?>
                    Tüm destek taleplerini görüntüleyin ve yönetin
                <?php elseif ($role === 'manager'): ?>
                    <?php echo getDepartmentName($userDept); ?> departmanına ait ticketları görüntüleyin
                <?php else: ?>
                    Kendi destek taleplerini görüntüleyin
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Filtre Kartı -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" class="form-inline">
                        <div class="form-group mr-3 mb-2">
                            <label class="mr-2"><i class="fas fa-search"></i> Ara:</label>
                            <input type="text" class="form-control" name="search" value="<?php echo sanitize($search); ?>" placeholder="Ticket No veya Konu">
                        </div>
                        <div class="form-group mr-3 mb-2">
                            <label class="mr-2"><i class="fas fa-filter"></i> Durum:</label>
                            <select class="form-control" name="status">
                                <option value="">Tümü</option>
                                <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Açık</option>
                                <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>Atandı</option>
                                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>İşlemde</option>
                                <option value="waiting" <?php echo $status_filter === 'waiting' ? 'selected' : ''; ?>>Beklemede</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>İptal</option>
                            </select>
                        </div>
                        <div class="form-group mr-3 mb-2">
                            <label class="mr-2"><i class="fas fa-exclamation-triangle"></i> Öncelik:</label>
                            <select class="form-control" name="priority">
                                <option value="">Tümü</option>
                                <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Düşük</option>
                                <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Orta</option>
                                <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>Yüksek</option>
                                <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Acil</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2 mr-2">
                            <i class="fas fa-search"></i> Filtrele
                        </button>
                        <a href="tickets.php" class="btn btn-secondary mb-2">
                            <i class="fas fa-redo"></i> Temizle
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Listesi -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list"></i> Ticket Listesi (<?php echo count($tickets); ?> kayıt)</h5>
                    <a href="new_ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Yeni Ticket
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Ticket No</th>
                                    <th>Konu</th>
                                    <?php if ($role !== 'user'): ?>
                                        <th>Kullanıcı</th>
                                    <?php endif; ?>
                                    <th>Kategori</th>
                                    <th>Durum</th>
                                    <th>Öncelik</th>
                                    <?php if ($role === 'admin'): ?>
                                        <th>Atanan</th>
                                    <?php endif; ?>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p>Henüz ticket bulunmuyor.</p>
                                            <a href="new_ticket.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> İlk Ticketı Oluştur
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <?php
                                        // Detay görüntüleme yetkisi kontrolü
                                        $can_view_detail = canViewTicketDetail($ticket, $userId, $role, $userDept);
                                        ?>
                                        <tr <?php echo !$can_view_detail ? 'style="opacity: 0.6;"' : ''; ?>>
                                            <td><strong><?php echo sanitize($ticket['ticket_number']); ?></strong></td>
                                            <td>
                                                <?php if ($can_view_detail): ?>
                                                    <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>">
                                                        <?php echo sanitize(substr($ticket['subject'], 0, 50)); ?>
                                                        <?php echo strlen($ticket['subject']) > 50 ? '...' : ''; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted" title="Bu ticketın detayını görüntüleme yetkiniz yok">
                                                        <?php echo sanitize(substr($ticket['subject'], 0, 30)); ?>...
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($role !== 'user'): ?>
                                                <td>
                                                    <?php echo sanitize($ticket['user_name']); ?>
                                                    <?php echo getDepartmentBadge($ticket['user_department']); ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php if ($ticket['category_name']): ?>
                                                    <span class="badge badge-secondary"><?php echo sanitize($ticket['category_name']); ?></span>
                                                <?php else: ?>
                                                    <em>-</em>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                            <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                            <?php if ($role === 'admin'): ?>
                                                <td><?php echo $ticket['assigned_name'] ? sanitize($ticket['assigned_name']) : '<em>Atanmadı</em>'; ?></td>
                                            <?php endif; ?>
                                            <td><small><?php echo timeAgo($ticket['created_at']); ?></small></td>
                                            <td>
                                                <?php if ($can_view_detail): ?>
                                                    <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary" title="Detay">
                                                        <i class="fas fa-eye"></i> Görüntüle
                                                    </a>
                                                    <?php if (isAdmin()): ?>
                                                        <button class="btn btn-sm btn-secondary" onclick="showLogs(<?php echo $ticket['id']; ?>, '<?php echo sanitize($ticket['ticket_number']); ?>')" title="İşlem Geçmişi">
                                                            <i class="fas fa-history"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Bu ticketın detayını görüntüleme yetkiniz yok">
                                                        <i class="fas fa-lock"></i> Kilitli
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history"></i> İşlem Geçmişi - <span id="logTicketNumber"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="logModalBody">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Yükleniyor...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showLogs(ticketId, ticketNumber) {
        document.getElementById('logTicketNumber').innerText = ticketNumber;
        $('#logModal').modal('show');

        fetch('logs_modal.php?ticket_id=' + ticketId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('logModalBody').innerHTML = data.html;
                } else {
                    document.getElementById('logModalBody').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('logModalBody').innerHTML = '<div class="alert alert-danger">Bir hata oluştu</div>';
            });
    }
</script>

<?php include 'footer.php'; ?>