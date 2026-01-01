<?php
require_once 'config.php';
requireLogin();

// SSS listesini çek
$stmt = $pdo->query("SELECT f.*, c.name as category_name 
                     FROM faq f 
                     LEFT JOIN categories c ON f.category_id = c.id 
                     WHERE f.is_active = 1 
                     ORDER BY f.order_num, f.id");
$faqs = $stmt->fetchAll();

// Kategorilere göre grupla
$grouped_faqs = [];
foreach ($faqs as $faq) {
    $cat = $faq['category_name'] ?? 'Genel';
    $grouped_faqs[$cat][] = $faq;
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2><i class="fas fa-question-circle"></i> Sık Sorulan Sorular (SSS)</h2>
            <p class="text-muted">Sıkça sorulan sorulara hızlıca göz atın</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8 offset-md-2">
            <div class="input-group">
                <input type="text" id="faq-search" class="form-control form-control-lg" placeholder="SSS içinde ara...">
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 offset-md-1">
            <?php if (empty($faqs)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <p>Henüz SSS eklenmemiş.</p>
                </div>
            <?php else: ?>

                <div id="faq-accordion">
                    <?php $counter = 0; ?>
                    <?php foreach ($grouped_faqs as $category => $category_faqs): ?>

                        <div class="card mb-3 text-primary">

                            <div class="card-body p-0">
                                <?php foreach ($category_faqs as $faq): ?>
                                    <?php $counter++; ?>
                                    <div class="faq-item border-bottom">
                                        <div class="faq-question p-3" style="cursor: pointer; background: #f8f9fa;"
                                            data-toggle="collapse" data-target="#faq-<?php echo $counter; ?>">
                                            <i class="fas fa-chevron-right mr-2 faq-icon"></i>
                                            <strong><?php echo sanitize($faq['question']); ?></strong>
                                        </div>
                                        <div id="faq-<?php echo $counter; ?>" class="collapse faq-answer">
                                            <div class="p-3">
                                                <?php echo nl2br(sanitize($faq['answer'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Yardım Kartı -->
    <div class="card mt-4">
        <div class="card-body text-center">
            <h5><i class="fas fa-headset"></i> Sorunuzu bulamadınız mı?</h5>
            <p class="text-muted">Destek ekibimiz size yardımcı olmaktan mutluluk duyacaktır.</p>
            <a href="new_ticket.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Destek Talebi Oluştur
            </a>
        </div>
    </div>
    </div>
</div>
</div>

<style>
    .faq-item:last-child {
        border-bottom: none !important;
    }

    .faq-question:hover {
        background: #e9ecef !important;
    }

    .faq-icon {
        transition: transform 0.3s;
    }

    .collapsed .faq-icon {
        transform: rotate(90deg);
    }
</style>

<script>
    $(document).ready(function() {
        // FAQ arama
        $('#faq-search').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();

            $('.faq-item').each(function() {
                var questionText = $(this).find('.faq-question').text().toLowerCase();
                var answerText = $(this).find('.faq-answer').text().toLowerCase();

                if (questionText.includes(searchText) || answerText.includes(searchText)) {
                    $(this).show();
                    if (searchText.length > 2) {
                        $(this).find('.collapse').addClass('show');
                    }
                } else {
                    $(this).hide();
                }
            });
        });

        // İkon animasyonu
        $('.faq-question').on('click', function() {
            $(this).find('.faq-icon').toggleClass('collapsed');
        });
    });
</script>

<?php include 'footer.php'; ?>