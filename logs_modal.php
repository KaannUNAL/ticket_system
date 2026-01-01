<?php
// Bu dosyayı AJAX ile çağırıp log verilerini döndürmek için kullanın
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

$ticket_id = $_GET['ticket_id'] ?? 0;

if ($ticket_id) {
    $stmt = $pdo->prepare("SELECT h.*, u.name as user_name
                           FROM ticket_history h
                           LEFT JOIN users u ON h.user_id = u.id
                           WHERE h.ticket_id = ?
                           ORDER BY h.created_at DESC");
    $stmt->execute([$ticket_id]);
    $logs = $stmt->fetchAll();

    $html = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Tarih & Saat</th>
                            <th>Kullanıcı</th>
                            <th>İşlem</th>
                            <th>Eski Değer</th>
                            <th>Yeni Değer</th>
                        </tr>
                    </thead>
                    <tbody>';

    if (empty($logs)) {
        $html .= '<tr><td colspan="5" class="text-center text-muted">Henüz log kaydı yok</td></tr>';
    } else {
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

        foreach ($logs as $log) {
            $action = $action_labels[$log['action']] ?? '<span class="badge badge-secondary">' . htmlspecialchars($log['action']) . '</span>';
            $old_value = $log['old_value'] ? htmlspecialchars($log['old_value']) : '-';
            $new_value = $log['new_value'] ? htmlspecialchars($log['new_value']) : '-';

            $html .= '<tr>
                        <td><small>' . date('d.m.Y H:i:s', strtotime($log['created_at'])) . '</small></td>
                        <td><small>' . htmlspecialchars($log['user_name']) . '</small></td>
                        <td>' . $action . '</td>
                        <td><small>' . $old_value . '</small></td>
                        <td><small>' . $new_value . '</small></td>
                    </tr>';
        }
    }

    $html .= '</tbody></table></div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Ticket ID bulunamadı'
    ]);
}
