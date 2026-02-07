<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package GF_French_Schools
 */

// Abort if not called by WordPress.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-ecoles-local-db.php';

// Drop the custom database table and remove associated options.
GF_Ecoles_Local_DB::drop_table();

// Remove any remaining scheduled cron events.
$timestamp = wp_next_scheduled('gf_ecoles_fr_sync');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'gf_ecoles_fr_sync');
}
$timestamp = wp_next_scheduled('gf_ecoles_fr_initial_sync');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'gf_ecoles_fr_initial_sync');
}
