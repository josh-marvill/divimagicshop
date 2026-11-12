<?php
/**
 * Plugin Name: Divimagicshop Core
 * Description: Tutorials and FAQ post types, the shop's blocks, and placeholder content. Lives in mu-plugins so a theme change never loses content.
 * Version: 1.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 */

defined('ABSPATH') || exit;

const DMS_CORE_VERSION = '1.2.0';
const DMS_CORE_DIR = __DIR__.'/divimagicshop-core';

require DMS_CORE_DIR.'/src/post-types.php';
require DMS_CORE_DIR.'/src/tutorial-meta.php';
require DMS_CORE_DIR.'/src/blocks.php';
require DMS_CORE_DIR.'/src/shop-blocks.php';
require DMS_CORE_DIR.'/src/seed.php';
