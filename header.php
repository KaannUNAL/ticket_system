<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --secondary: #3b82f6;
            --accent: #60a5fa;
            --dark: #0f172a;
            --light: #f8fafc;
            --header-height: 70px;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-attachment: fixed;
            padding-top: var(--header-height);
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.3) 0%, transparent 50%);
            z-index: -1;
        }



        /* Ultra Modern Header */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 0;
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            padding: 0 30px;
        }

        /* Logo Section */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 800;
            font-size: 22px;
            color: var(--primary) !important;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 0;
            height: auto;
        }

        .navbar-brand:hover {
            transform: translateY(-2px);
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.3);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 11px;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Navigation */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px !important;
            color: #475569 !important;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.3);
        }

        .nav-link i {
            font-size: 16px;
        }

        /* Right Section */
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Notification Bell */
        .notification-wrapper {
            position: relative;
        }

        .notification-bell {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }

        .notification-bell:hover {
            transform: translateY(-2px) rotate(15deg);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .notification-bell i {
            font-size: 20px;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 3px 7px;
            font-size: 11px;
            font-weight: 700;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        /* Notification Dropdown */
        .notification-dropdown {
            position: absolute;
            top: calc(100% + 15px);
            right: 0;
            width: 420px;
            max-height: 550px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            display: none;
            z-index: 1000;
            overflow: hidden;
        }

        .notification-dropdown::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
        }

        .notification-dropdown.show {
            display: block;
            animation: dropdownSlide 0.3s ease;
        }

        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h6 {
            font-weight: 700;
            margin: 0;
            font-size: 16px;
        }

        .notification-header a {
            color: white;
            font-size: 12px;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .notification-header a:hover {
            opacity: 1;
        }

        .notification-list {
            max-height: 450px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 18px 25px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            gap: 15px;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #eff6ff;
            border-left: 3px solid var(--secondary);
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .notification-icon.new-ticket {
            background: #dbeafe;
            color: #1e40af;
        }

        .notification-icon.new-message {
            background: #d1fae5;
            color: #059669;
        }

        .notification-icon.status-change {
            background: #fed7aa;
            color: #ea580c;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content strong {
            color: #0f172a;
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }

        .notification-content small {
            color: #64748b;
            font-size: 12px;
        }

        /* User Section */
        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.3);
        }

        .user-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.4);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }

        .user-name {
            color: white;
            font-weight: 700;
            font-size: 14px;
        }

        .user-role {
            color: rgba(255, 255, 255, 0.8);
            font-size: 11px;
            font-weight: 500;
        }

        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: none;
            margin-top: 15px;
            padding: 10px;
        }

        .dropdown-item {
            padding: 12px 20px;
            transition: all 0.3s;
            border-radius: 8px;
            font-weight: 500;
            color: #475569;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            color: var(--primary);
            padding-left: 25px;
        }

        .dropdown-item i {
            width: 20px;
            margin-right: 12px;
        }

        /* Content Area */
        .container-fluid,
        .container {
            background: transparent;
        }

        .card {
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 20px 25px;
            font-weight: 700;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-card h3 {
            font-size: 36px;
            font-weight: 800;
            margin: 15px 0 8px 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 45px;
            opacity: 0.15;
            float: right;
        }

        .badge {
            padding: 6px 14px;
            font-weight: 600;
            border-radius: 8px;
            font-size: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.4);
        }

        .table th {
            border-top: none;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-container">
                <!-- Logo -->
                <a class="navbar-brand" href="dashboard.php">
                    <div class="brand-icon">
                        <i class="fas fa-ship"></i>
                    </div>
                    <div class="brand-text">
                        <div class="brand-name">TANKMARINE</div>
                        <div class="brand-subtitle">Ticket System</div>
                    </div>
                </a>

                <!-- Navigation Menu -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i>
                            Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tickets.php">
                            <i class="fas fa-ticket-alt"></i>
                            Ticketlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="new_ticket.php">
                            <i class="fas fa-plus-circle"></i>
                            Yeni Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="faq.php">
                            <i class="fas fa-question-circle"></i>
                            SSS
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-cog"></i>
                                Yönetim
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Right Section -->
                <div class="navbar-right">
                    <!-- Notifications -->
                    <div class="notification-wrapper">
                        <div class="notification-bell" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php
                            $unread_count = getUnreadNotificationCount(getUserId());
                            if ($unread_count > 0):
                            ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h6><i class="fas fa-bell"></i> Bildirimler</h6>
                                <a href="notifications.php?mark_all_read=1">
                                    <i class="fas fa-check-double"></i> Tümünü Okundu İşaretle
                                </a>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="text-center p-3">
                                    <i class="fas fa-spinner fa-spin"></i> Yükleniyor...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User -->
                    <div class="dropdown">
                        <div class="user-section" data-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo sanitize($_SESSION['user_name']); ?></div>
                                <div class="user-role"><?php echo getDepartmentName($_SESSION['department']); ?></div>
                            </div>
                            <i class="fas fa-chevron-down" style="color: white; font-size: 12px;"></i>
                        </div>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-item disabled" style="opacity: 1; background: #f8fafc;">
                                <?php echo getRoleBadge($_SESSION['role']); ?>
                                <?php echo getDepartmentBadge($_SESSION['department']); ?>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user"></i> Profil
                            </a>
                            <?php if (isManager()): ?>
                                <a class="dropdown-item" href="approvals.php">
                                    <i class="fas fa-check-circle"></i> Onay Bekleyenler
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Bildirim panel toggle
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');

            if (dropdown.classList.contains('show')) {
                loadNotifications();
            }
        }

        // Dışarı tıklayınca kapat
        document.addEventListener('click', function(event) {
            const wrapper = document.querySelector('.notification-wrapper');
            const dropdown = document.getElementById('notificationDropdown');

            if (wrapper && !wrapper.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Bildirimleri yükle
        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');

                    if (data.notifications.length === 0) {
                        list.innerHTML = '<div class="text-center p-4 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><p>Bildirim yok</p></div>';
                        return;
                    }

                    list.innerHTML = data.notifications.map(n => `
                <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" onclick="markAsRead(${n.id}, ${n.ticket_id})">
                    <div class="notification-icon ${n.type}">
                        <i class="fas ${getNotificationIcon(n.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <strong>${escapeHtml(n.message)}</strong>
                        <small><i class="far fa-clock"></i> ${n.time_ago}</small>
                    </div>
                </div>
            `).join('');
                });
        }

        function getNotificationIcon(type) {
            const icons = {
                'new_ticket': 'fa-ticket-alt',
                'new_message': 'fa-comment',
                'status_change': 'fa-sync',
                'assigned': 'fa-user-tag'
            };
            return icons[type] || 'fa-bell';
        }

        function markAsRead(notificationId, ticketId) {
            fetch(`notifications.php?mark_read=${notificationId}`)
                .then(() => {
                    window.location.href = `ticket_detail.php?id=${ticketId}`;
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 30 saniyede bir bildirimleri güncelle
        setInterval(function() {
            if (document.getElementById('notificationDropdown').classList.contains('show')) {
                loadNotifications();
            }
        }, 30000);
    </script>