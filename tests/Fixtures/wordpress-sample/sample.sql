CREATE TABLE `wp_options` (
  `option_id` bigint unsigned NOT NULL,
  `option_name` varchar(191) NOT NULL,
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL
);

CREATE TABLE `wp_posts` (
  `ID` bigint unsigned NOT NULL,
  `post_type` varchar(20) NOT NULL
);

CREATE TABLE `wp_postmeta` (
  `meta_id` bigint unsigned NOT NULL,
  `post_id` bigint unsigned NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext
);

CREATE TABLE `wp_comments` (
  `comment_ID` bigint unsigned NOT NULL
);

CREATE TABLE `wp_terms` (
  `term_id` bigint unsigned NOT NULL
);

CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint unsigned NOT NULL
);

CREATE TABLE `wp_term_relationships` (
  `object_id` bigint unsigned NOT NULL
);

INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(1, 'siteurl', 'https://example.test', 'yes'),
(2, 'home', 'https://example.test', 'yes'),
(3, 'db_version', '49752', 'yes'),
(4, 'permalink_structure', '/%postname%/', 'yes'),
(5, 'template', 'sample-theme', 'yes'),
(6, 'stylesheet', 'sample-theme', 'yes'),
(7, 'active_plugins', 'a:2:{i:0;s:53:"easy-wp-meta-description/easy-wp-meta-description.php";i:1;s:19:"sample-seo/plugin.php";}', 'yes');

INSERT INTO `wp_posts` (`ID`, `post_type`) VALUES
(1, 'post'),
(2, 'page'),
(3, 'nav_menu_item');

INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(1, 1, '_field_41', 'Great site. https://wphasslefree.club/wpmaint?=example.test');
