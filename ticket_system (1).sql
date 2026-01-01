-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 01 Oca 2026, 23:59:46
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `ticket_system`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(50) DEFAULT 'genel',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `department`, `created_at`) VALUES
(1, 'Muhasebe - Fatura', 'Fatura işlemleri ve sorunları', 'muhasebe', '2025-12-31 23:50:01'),
(2, 'Muhasebe - Ödeme', 'Ödeme ve tahsilat işlemleri', 'muhasebe', '2025-12-31 23:50:01'),
(3, 'Operasyon - Lojistik', 'Lojistik ve kargo işlemleri', 'operasyon', '2025-12-31 23:50:01'),
(4, 'Operasyon - Süreç', 'Operasyonel süreç iyileştirme', 'operasyon', '2025-12-31 23:50:01'),
(5, 'İnsan Kaynakları - İşe Alım', 'İşe alım süreçleri', 'insan_kaynaklari', '2025-12-31 23:50:01'),
(6, 'İnsan Kaynakları - Maaş', 'Maaş ve özlük işlemleri', 'insan_kaynaklari', '2025-12-31 23:50:01'),
(7, 'Teknik - Yazılım', 'Yazılım sorunları ve geliştirme', 'teknik', '2025-12-31 23:50:01'),
(8, 'Teknik - Donanım', 'Donanım sorunları ve bakım', 'teknik', '2025-12-31 23:50:01'),
(9, 'Genel - Diğer', 'Diğer talepler', 'genel', '2025-12-31 23:50:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'muhasebe', 'Muhasebe', 'Muhasebe ve finans departmanı', 1, '2025-12-31 23:50:01'),
(2, 'operasyon', 'Operasyon', 'Operasyon ve lojistik departmanı', 1, '2025-12-31 23:50:01'),
(3, 'insan_kaynaklari', 'İnsan Kaynakları', 'İnsan kaynakları departmanı', 1, '2025-12-31 23:50:01'),
(4, 'teknik', 'Teknik', 'Teknik destek ve IT departmanı', 1, '2025-12-31 23:50:01'),
(5, 'genel', 'Genel', 'Genel departman', 1, '2025-12-31 23:50:01'),
(7, 'bt', 'Information Technology', 'BT', 1, '2026-01-01 01:01:47');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `order_num` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `faq`
--

INSERT INTO `faq` (`id`, `question`, `answer`, `category_id`, `order_num`, `is_active`, `views`, `created_at`, `updated_at`) VALUES
(1, 'Ticket nasıl açarım?', 'Üst menüden \"Yeni Ticket\" butonuna tıklayarak ticket açabilirsiniz. Konu ve açıklamanızı girdikten sonra gönder butonuna basın.', 1, 1, 1, 0, '2025-12-31 23:50:01', '2025-12-31 23:50:01'),
(2, 'Başka departmana ticket açabilir miyim?', 'Evet, ancak kendi departmanınızın dışındaki bir kategoride ticket açmak için yöneticinizin onayı gerekir.', 1, 2, 1, 0, '2025-12-31 23:50:01', '2025-12-31 23:50:01'),
(3, 'Ticketımın durumunu nasıl takip ederim?', 'Dashboard sayfasından tüm ticketlarınızı görebilir ve detaylarına tıklayarak durumunu takip edebilirsiniz.', 1, 3, 1, 0, '2025-12-31 23:50:01', '2025-12-31 23:50:01'),
(4, 'Şifremi unuttum ne yapmalıyım?', 'Giriş sayfasında \"Şifremi Unuttum\" linkine tıklayarak şifre sıfırlama talebinde bulunabilirsiniz.', 2, 4, 1, 0, '2025-12-31 23:50:01', '2025-12-31 23:50:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `type` enum('new_ticket','new_message','status_change','assigned') DEFAULT 'new_message',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','assigned','in_progress','waiting','completed','cancelled') DEFAULT 'open',
  `attachment` varchar(255) DEFAULT NULL,
  `requires_approval` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `user_id`, `assigned_to`, `category_id`, `subject`, `description`, `priority`, `status`, `attachment`, `requires_approval`, `approved_by`, `created_at`, `updated_at`) VALUES
(2, 'TKT-20260101-78700A', 1, 1, 5, 'deneme', 'test', 'urgent', 'assigned', '1767228995_6955c643a3809.pdf', 0, NULL, '2026-01-01 00:56:35', '2026-01-01 12:53:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ticket_approvals`
--

CREATE TABLE `ticket_approvals` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ticket_attachments`
--

CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ticket_history`
--

CREATE TABLE `ticket_history` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ticket_history`
--

INSERT INTO `ticket_history` (`id`, `ticket_id`, `user_id`, `action`, `old_value`, `new_value`, `created_at`) VALUES
(10, 2, 1, 'created', NULL, 'TKT-20260101-78700A', '2026-01-01 00:56:35'),
(11, 2, 1, 'assigned', NULL, '1', '2026-01-01 12:53:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `user_id`, `message`, `is_internal`, `created_at`) VALUES
(5, 2, 1, 'test', 0, '2026-01-01 00:56:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','user') DEFAULT 'user',
  `department` varchar(50) DEFAULT 'genel',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `created_at`) VALUES
(1, 'Kaan ÜNAL', 'admin@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'admin', 'bt', '2025-12-31 23:50:01'),
(2, 'Muhasebe Müdürü', 'muhasebe.mudur@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'manager', 'muhasebe', '2025-12-31 23:50:01'),
(3, 'Muhasebe Kullanıcı', 'muhasebe@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'user', 'muhasebe', '2025-12-31 23:50:01'),
(4, 'Operasyon Müdürü', 'operasyon.mudur@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'manager', 'operasyon', '2025-12-31 23:50:01'),
(5, 'Operasyon Kullanıcı', 'operasyon@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'user', 'operasyon', '2025-12-31 23:50:01'),
(6, 'İK Müdürü', 'ik.mudur@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'manager', 'insan_kaynaklari', '2025-12-31 23:50:01'),
(7, 'İK Kullanıcı', 'ik@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'user', 'insan_kaynaklari', '2025-12-31 23:50:01'),
(8, 'Teknik Müdür', 'teknik.mudur@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'manager', 'teknik', '2025-12-31 23:50:01'),
(9, 'Teknik Kullanıcı', 'teknik@ticket.com', '$2y$10$SUD6eg98NrgdbzX2T/FPw.MEhUcF4VMrRbZ7hUdu75aF9cDAPh.Em', 'user', 'teknik', '2025-12-31 23:50:01');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department` (`department`);

--
-- Tablo için indeksler `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Tablo için indeksler `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`order_num`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `ticket_approvals`
--
ALTER TABLE `ticket_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_status` (`status`);

--
-- Tablo için indeksler `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_ticket` (`ticket_id`);

--
-- Tablo için indeksler `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ticket` (`ticket_id`);

--
-- Tablo için indeksler `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_department` (`department`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `ticket_approvals`
--
ALTER TABLE `ticket_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ticket_history`
--
ALTER TABLE `ticket_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`department`) REFERENCES `departments` (`code`) ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `faq`
--
ALTER TABLE `faq`
  ADD CONSTRAINT `faq_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `ticket_approvals`
--
ALTER TABLE `ticket_approvals`
  ADD CONSTRAINT `ticket_approvals_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_approvals_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_approvals_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_approvals_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD CONSTRAINT `ticket_history_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department`) REFERENCES `departments` (`code`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
