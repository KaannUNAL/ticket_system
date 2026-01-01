<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';
$warning = '';

$userId = getUserId();
$userDept = getUserDepartment();
$userRole = getUserRole();

// Kategorileri çek
$categories = $pdo->query("SELECT * FROM categories ORDER BY department, name")->fetchAll();

// Kullanıcının kendi departman kategorilerini ayır
$my_dept_categories = [];
$other_dept_categories = [];

foreach ($categories as $cat) {
    if ($cat['department'] === $userDept || $userRole === 'admin') {
        $my_dept_categories[] = $cat;
    } else {
        $other_dept_categories[] = $cat;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $requires_approval = isset($_POST['requires_approval']) ? true : false;

    if (empty($subject) || empty($description)) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } elseif (empty($category_id)) {
        $error = 'Lütfen bir kategori seçin.';
    } else {
        try {
            // Kategori bilgisini al
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $selected_category = $stmt->fetch();

            // Departman kontrolü
            $needs_approval = false;
            if ($userRole !== 'admin' && $selected_category['department'] !== $userDept) {
                $needs_approval = true;
            }

            $ticket_number = generateTicketNumber();
            $attachment = null;

            // Dosya yükleme işlemi
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_name = $_FILES['attachment']['name'];
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_size = $_FILES['attachment']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'];

                if ($file_size > 10 * 1024 * 1024) {
                    $error = 'Dosya boyutu en fazla 10MB olabilir.';
                } elseif (!in_array($file_ext, $allowed_extensions)) {
                    $error = 'İzin verilmeyen dosya türü. İzin verilen: ' . implode(', ', $allowed_extensions);
                } else {
                    $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $attachment = $new_file_name;
                    } else {
                        $error = 'Dosya yüklenirken bir hata oluştu.';
                    }
                }
            }

            if (empty($error)) {
                if ($needs_approval) {
                    // Onay bekleyen ticket oluştur
                    $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, user_id, category_id, subject, description, priority, status, attachment, requires_approval) 
                                           VALUES (?, ?, ?, ?, ?, ?, 'waiting', ?, 1)");
                    $stmt->execute([$ticket_number, $userId, $category_id, $subject, $description, $priority, $attachment]);

                    $ticket_id = $pdo->lastInsertId();

                    // Onay talebini kaydet
                    $stmt = $pdo->prepare("INSERT INTO ticket_approvals (ticket_id, user_id, category_id, reason, status) 
                                          VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->execute([$ticket_id, $userId, $category_id, 'Farklı departman kategorisi seçildi']);

                    // Geçmişe kaydet
                    $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) VALUES (?, ?, 'created_pending', ?)");
                    $stmt->execute([$ticket_id, $userId, $ticket_number]);

                    $warning = "Ticket oluşturuldu ancak farklı departman kategorisi seçtiğiniz için yönetici onayı bekleniyor. Ticket No: $ticket_number";

                    header("refresh:3;url=tickets.php");
                } else {
                    // Normal ticket oluştur
                    $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, user_id, category_id, subject, description, priority, status, attachment) 
                                           VALUES (?, ?, ?, ?, ?, ?, 'open', ?)");
                    $stmt->execute([$ticket_number, $userId, $category_id, $subject, $description, $priority, $attachment]);

                    $ticket_id = $pdo->lastInsertId();

                    // İlk mesajı ekle
                    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
                    $stmt->execute([$ticket_id, $userId, $description]);

                    // Geçmişe kaydet
                    $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, user_id, action, new_value) VALUES (?, ?, 'created', ?)");
                    $stmt->execute([$ticket_id, $userId, $ticket_number]);

                    $success = "Ticket başarıyla oluşturuldu! Ticket No: $ticket_number";

                    header("refresh:2;url=ticket_detail.php?id=$ticket_id");
                }
            }
        } catch (PDOException $e) {
            $error = 'Ticket oluşturulurken bir hata oluştu.';
        }
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plus-circle"></i> Yeni Destek Talebi Oluştur</h5>
                </div>
                <div class="card-body">
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

                    <?php if ($warning): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $warning; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" id="ticketForm">
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-heading"></i> Konu *</label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                placeholder="Sorun veya talebinizi kısaca özetleyin" required
                                value="<?php echo isset($_POST['subject']) ? sanitize($_POST['subject']) : ''; ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id"><i class="fas fa-folder"></i> Kategori *</label>
                                    <select class="form-control" id="category_id" name="category_id" required onchange="checkDepartment()">
                                        <option value="">Kategori Seçin</option>
                                        <?php if (!empty($my_dept_categories)): ?>
                                            <optgroup label="<?php echo getDepartmentName($userDept); ?> (Kendi Departmanınız)">
                                                <?php foreach ($my_dept_categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" data-dept="<?php echo $cat['department']; ?>">
                                                        <?php echo sanitize($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        <?php if (!empty($other_dept_categories)): ?>
                                            <optgroup label="Diğer Departmanlar (Yönetici Onayı Gerekir)">
                                                <?php foreach ($other_dept_categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" data-dept="<?php echo $cat['department']; ?>">
                                                        <?php echo sanitize($cat['name']); ?> - <?php echo getDepartmentName($cat['department']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                    <small id="categoryWarning" class="form-text text-danger" style="display:none;">
                                        <i class="fas fa-exclamation-triangle"></i> Bu kategori için yönetici onayı gerekecektir.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority"><i class="fas fa-exclamation-triangle"></i> Öncelik</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low">Düşük</option>
                                        <option value="medium" selected>Orta</option>
                                        <option value="high">Yüksek</option>
                                        <option value="urgent">Acil</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Açıklama *</label>
                            <textarea class="form-control" id="description" name="description" rows="8"
                                placeholder="Sorununuzu veya talebinizi detaylı olarak açıklayın..." required><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Lütfen sorununuzu olabildiğince detaylı açıklayın.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="attachment"><i class="fas fa-paperclip"></i> Dosya Ekle (Opsiyonel)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="attachment" name="attachment"
                                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                                <label class="custom-file-label" for="attachment">Dosya seçin...</label>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Maks. 10MB (JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR)
                            </small>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-paper-plane"></i> Ticket Oluştur
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-lightbulb"></i> Ticket Açmadan Önce</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><a href="faq.php">SSS sayfasını</a> kontrol ettiniz mi?</li>
                        <li>Kendi departmanınızın kategorilerinden birini seçin.</li>
                        <li>Farklı departman kategorisi seçerseniz yönetici onayı gerekir.</li>
                        <li>Sorununuzu olabildiğince detaylı açıklayın.</li>
                        <li>Gerekirse ekran görüntüsü veya ilgili dosyaları ekleyin.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Çoklu dosya seçimi için etiket güncelleme
    $('#attachments').on('change', function() {
        var files = $(this)[0].files;
        var fileNames = [];

        if (files.length > 0) {
            for (var i = 0; i < files.length; i++) {
                fileNames.push(files[i].name);
            }
            $(this).next('.custom-file-label').html(files.length + ' dosya seçildi');

            // Dosya listesini göster
            var fileListHtml = '<div class="alert alert-info"><strong>Seçilen dosyalar:</strong><ul class="mb-0 mt-2">';
            fileNames.forEach(function(name) {
                fileListHtml += '<li>' + name + '</li>';
            });
            fileListHtml += '</ul></div>';
            $('#fileList').html(fileListHtml);
        } else {
            $(this).next('.custom-file-label').html('Dosya(lar) seçin...');
            $('#fileList').html('');
        }
    });

    // Departman kontrolü
    function checkDepartment() {
        var selectedOption = $('#category_id option:selected');
        var selectedDept = selectedOption.data('dept');
        var userDept = '<?php echo $userDept; ?>';
        var isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;

        if (!isAdmin && selectedDept && selectedDept !== userDept) {
            $('#categoryWarning').show();
        } else {
            $('#categoryWarning').hide();
        }
    }
</script>

<?php include 'footer.php'; ?>