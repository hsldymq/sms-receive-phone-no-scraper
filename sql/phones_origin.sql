CREATE TABLE `phones_origin` (
 `phone_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `calling_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `origin` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 UNIQUE KEY `phones_origin_pk` (`phone_no`,`calling_code`,`origin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;