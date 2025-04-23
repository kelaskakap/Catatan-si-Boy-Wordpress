<?php
/*
Plugin Name: Catatan si Boy
Description: Plugin sticky notes sederhana untuk dashboard admin
Version: 1.0
Author: Anda
*/

defined('ABSPATH') or die('No script kiddies please!');

// Definisikan konstanta plugin
define('CATATAN_SI_BOY_VERSION', '1.0');
define('CATATAN_SI_BOY_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include file dependencies
require_once CATATAN_SI_BOY_PLUGIN_DIR . 'includes/class-database.php';
require_once CATATAN_SI_BOY_PLUGIN_DIR . 'includes/class-notes.php';
require_once CATATAN_SI_BOY_PLUGIN_DIR . 'includes/class-admin-ui.php';

// Inisialisasi
register_activation_hook(__FILE__, ['CatatanSiBoy_Database', 'activate']);
register_deactivation_hook(__FILE__, ['CatatanSiBoy_Database', 'deactivate']);

add_action('plugins_loaded', function ()
{
    new CatatanSiBoy_Notes();
    new CatatanSiBoy_Admin_UI();
});

add_action('admin_enqueue_scripts', function ($hook)
{
    if ('index.php' === $hook)
    {
        // Load Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery']);

        // Load TinyMCE
        wp_enqueue_editor();

        // Load plugin assets
        wp_enqueue_style(
            'catatan-si-boy-css',
            plugins_url('assets/css/admin.css', __FILE__),
            [],
            CATATAN_SI_BOY_VERSION
        );

        wp_enqueue_script(
            'catatan-si-boy-js',
            plugins_url('assets/js/admin.js', __FILE__),
            ['jquery', 'jquery-ui-draggable', 'select2'],
            CATATAN_SI_BOY_VERSION,
            true
        );

        wp_localize_script('catatan-si-boy-js', 'catatanSiBoy', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('catatan-si-boy-nonce')
        ]);
    }
});

add_action('wp_ajax_get_users', 'get_users_callback');
function get_users_callback()
{
    $users = array_map(function ($u)
    {
        return ['ID' => $u->ID, 'display_name' => $u->display_name];
    }, get_users(['fields' => ['ID', 'display_name']]));
    wp_send_json($users);
}
