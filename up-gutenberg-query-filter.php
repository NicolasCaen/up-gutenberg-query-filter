<?php
/**
 * Plugin Name:       Gutenberg Query Filter
 * Description:       Filter blocks for the query loop utilising the interactivity API.
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Version:           1.1.6
 * Author:            Upcoder
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       up-gutenberg-query-filter
 *
 * @package           query-loop-filter
 */
namespace up\query_loop_filter;

const PLUGIN_FILE = __FILE__;
const ROOT_DIR = __DIR__;

require_once __DIR__ . '/inc/namespace.php';

bootstrap();
