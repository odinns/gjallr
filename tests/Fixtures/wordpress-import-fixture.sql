DROP TABLE IF EXISTS `wp_term_relationships`;
DROP TABLE IF EXISTS `wp_term_taxonomy`;
DROP TABLE IF EXISTS `wp_terms`;
DROP TABLE IF EXISTS `wp_comments`;
DROP TABLE IF EXISTS `wp_postmeta`;
DROP TABLE IF EXISTS `wp_posts`;
DROP TABLE IF EXISTS `wp_options`;

CREATE TABLE `wp_options` (
  `option_id` bigint unsigned NOT NULL,
  `option_name` varchar(191) NOT NULL,
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL
);

CREATE TABLE `wp_posts` (
  `ID` bigint unsigned NOT NULL,
  `post_author` bigint unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL,
  `post_date_gmt` datetime NOT NULL,
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL,
  `comment_status` varchar(20) NOT NULL,
  `ping_status` varchar(20) NOT NULL,
  `post_name` varchar(200) NOT NULL,
  `post_parent` bigint unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL,
  `menu_order` int NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL,
  `post_mime_type` varchar(100) NOT NULL DEFAULT ''
);

CREATE TABLE `wp_postmeta` (
  `meta_id` bigint unsigned NOT NULL,
  `post_id` bigint unsigned NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext
);

CREATE TABLE `wp_comments` (
  `comment_ID` bigint unsigned NOT NULL,
  `comment_post_ID` bigint unsigned NOT NULL,
  `comment_author` text NOT NULL,
  `comment_author_email` varchar(100) NOT NULL,
  `comment_date` datetime NOT NULL,
  `comment_date_gmt` datetime NOT NULL,
  `comment_content` text NOT NULL,
  `comment_approved` varchar(20) NOT NULL,
  `comment_parent` bigint unsigned NOT NULL DEFAULT 0
);

CREATE TABLE `wp_terms` (
  `term_id` bigint unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL
);

CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `taxonomy` varchar(32) NOT NULL,
  `description` longtext NOT NULL,
  `parent` bigint unsigned NOT NULL DEFAULT 0
);

CREATE TABLE `wp_term_relationships` (
  `object_id` bigint unsigned NOT NULL,
  `term_taxonomy_id` bigint unsigned NOT NULL
);

INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(1, 'blogname', 'Fixture Site', 'yes'),
(2, 'siteurl', 'https://fixture.test', 'yes'),
(3, 'home', 'https://fixture.test', 'yes'),
(4, 'db_version', '49752', 'yes'),
(5, 'permalink_structure', '/%postname%/', 'yes'),
(6, 'template', 'sample-theme', 'yes'),
(7, 'stylesheet', 'sample-theme', 'yes'),
(8, 'show_on_front', 'page', 'yes'),
(9, 'page_on_front', '2', 'yes'),
(10, 'page_for_posts', '3', 'yes'),
(11, 'active_plugins', 'a:1:{i:0;s:53:"easy-wp-meta-description/easy-wp-meta-description.php";}', 'yes');

INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_name`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`) VALUES
(1, 1, '2024-01-01 10:00:00', '2024-01-01 10:00:00', '<p>Hello from the rescued post.</p><p><img src="https://fixture.test/wp-content/uploads/2021/05/demo.jpg"></p>', 'Hello World', 'A short rescued excerpt.', 'publish', 'open', 'closed', 'hello-world', 0, 'https://fixture.test/?p=1', 0, 'post', ''),
(2, 1, '2024-01-02 10:00:00', '2024-01-02 10:00:00', '<p>This is the front page body.</p>', 'Front Page', '', 'publish', 'closed', 'closed', 'front-page', 0, 'https://fixture.test/?page_id=2', 0, 'page', ''),
(3, 1, '2024-01-03 10:00:00', '2024-01-03 10:00:00', '<p>Blog index placeholder.</p>', 'Blog', '', 'publish', 'closed', 'closed', 'blog', 0, 'https://fixture.test/?page_id=3', 0, 'page', ''),
(7, 1, '2024-01-03 12:00:00', '2024-01-03 12:00:00', '<p>Draft page body.</p>', 'Working Draft', '', 'draft', 'closed', 'closed', 'working-draft', 0, 'https://fixture.test/?page_id=7', 0, 'page', ''),
(8, 1, '2024-01-03 13:00:00', '2024-01-03 13:00:00', '<p>Private notes body.</p>', 'Private Notes', '', 'private', 'closed', 'closed', 'private-notes', 0, 'https://fixture.test/?page_id=8', 0, 'page', ''),
(9, 1, '2024-01-03 14:00:00', '2024-01-03 14:00:00', '<p>Parent page body.</p>', 'Behandlinger', '', 'private', 'closed', 'closed', 'behandlinger', 0, 'https://fixture.test/?page_id=9', 0, 'page', ''),
(10, 1, '2024-01-03 15:00:00', '2024-01-03 15:00:00', '<p>Room page body.</p>', 'Lokalet', '', 'private', 'closed', 'closed', 'lokalet', 9, 'https://fixture.test/?page_id=10', 0, 'page', ''),
(4, 1, '2024-01-04 10:00:00', '2024-01-04 10:00:00', '', '', '', 'publish', 'closed', 'closed', 'menu-home', 0, 'https://fixture.test/?p=4', 1, 'nav_menu_item', ''),
(5, 1, '2024-01-05 10:00:00', '2024-01-05 10:00:00', '', '', '', 'publish', 'closed', 'closed', 'menu-blog', 0, 'https://fixture.test/?p=5', 2, 'nav_menu_item', ''),
(6, 1, '2024-01-06 10:00:00', '2024-01-06 10:00:00', '', 'Demo image', '', 'inherit', 'closed', 'closed', 'demo-image', 1, 'https://fixture.test/wp-content/uploads/2021/05/demo.jpg', 0, 'attachment', 'image/jpeg');

INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(1, 1, 'meta_description', 'This rescued post still knows what it is.'),
(2, 4, '_menu_item_type', 'post_type'),
(3, 4, '_menu_item_menu_item_parent', '0'),
(4, 4, '_menu_item_object_id', '2'),
(5, 5, '_menu_item_type', 'post_type'),
(6, 5, '_menu_item_menu_item_parent', '0'),
(7, 5, '_menu_item_object_id', '3'),
(8, 6, '_wp_attached_file', '2021/05/demo.jpg'),
(9, 6, '_wp_attachment_image_alt', 'Fixture image alt');

INSERT INTO `wp_comments` (`comment_ID`, `comment_post_ID`, `comment_author`, `comment_author_email`, `comment_date`, `comment_date_gmt`, `comment_content`, `comment_approved`, `comment_parent`) VALUES
(1, 1, 'Alice', 'alice@example.test', '2024-01-07 10:00:00', '2024-01-07 10:00:00', 'First comment', '1', 0),
(2, 1, 'Bob', 'bob@example.test', '2024-01-07 11:00:00', '2024-01-07 11:00:00', 'Reply comment', '1', 1);

INSERT INTO `wp_terms` (`term_id`, `name`, `slug`) VALUES
(1, 'Primary', 'primary'),
(2, 'News', 'news');

INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`) VALUES
(1, 1, 'nav_menu', '', 0),
(2, 2, 'category', 'News posts', 0);

INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`) VALUES
(4, 1),
(5, 1),
(1, 2);
