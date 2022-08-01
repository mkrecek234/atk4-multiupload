CREATE TABLE `filestore_file` (
                                  `id` int unsigned NOT NULL AUTO_INCREMENT,
                                  `token` varchar(60) DEFAULT NULL,
                                  `location` varchar(400) DEFAULT NULL,
                                  `url` varchar(400) DEFAULT NULL,
                                  `storage` varchar(50) DEFAULT NULL,
                                  `status` varchar(10) DEFAULT NULL,
                                  `source_file_id` int DEFAULT NULL,
                                  `meta_filename` varchar(250) DEFAULT NULL,
                                  `meta_extension` varchar(10) DEFAULT NULL,
                                  `meta_md5` varchar(60) DEFAULT NULL,
                                  `meta_mime_type` varchar(255) DEFAULT NULL,
                                  `meta_size` int DEFAULT NULL,
                                  `meta_is_image` tinyint(1) DEFAULT NULL,
                                  `meta_image_width` int DEFAULT NULL,
                                  `meta_image_height` int DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `file_token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=70413 DEFAULT CHARSET=utf8mb3;

CREATE TABLE `friend` (
                          `id` int unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(128) DEFAULT NULL,
                          `file` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
                          `file2` varchar(512) DEFAULT NULL,
                          PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;