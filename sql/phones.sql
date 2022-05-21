CREATE TABLE `phones` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `phone_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calling_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code_2` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phones_phone_no_calling_code_uindex` (`phone_no`,`calling_code`)
) ENGINE=InnoDB AUTO_INCREMENT=23687 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

