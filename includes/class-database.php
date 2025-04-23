<?php
class CatatanSiBoy_Database
{
    public static function activate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'catatan_si_boy';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(100) DEFAULT '',
            content text NOT NULL,
            color varchar(20) DEFAULT '#fff9c4',
            shared_users text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function deactivate()
    {
        // Bersihkan data jika perlu
        // Atau biarkan tabel tetap ada untuk data tetap tersimpan
    }
}
