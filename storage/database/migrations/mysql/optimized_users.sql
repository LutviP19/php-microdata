-- 1. Tambahkan indeks unik B-Tree untuk ULID (karena nilainya harus unik per user)
ALTER TABLE `users` ADD UNIQUE KEY `users_ulid_unique` (`ulid`);

-- 2. Tambahkan indeks B-Tree tunggal untuk Phone (jika digunakan untuk login/pencarian)
ALTER TABLE `users` ADD KEY `users_phone_index` (`phone`);

-- 3. Tambahkan Composite Index (Indeks Gabungan) untuk Status dan Soft Delete
-- Sangat ampuh mempercepat query filter user aktif di admin panel
ALTER TABLE `users` ADD KEY `users_status_deleted_at_index` (`status`, `deleted_at`);

-- Fulltext index (Search)
ALTER TABLE `users` ADD FULLTEXT KEY `users_search_fulltext` (`name`, `address_line1`);
