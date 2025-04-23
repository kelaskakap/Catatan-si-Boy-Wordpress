<?php
class CatatanSiBoy_Notes
{
    public function __construct()
    {
        add_action('wp_ajax_save_catatan', [$this, 'save_note']);
        add_action('wp_ajax_delete_catatan', [$this, 'delete_note']);
        add_action('wp_ajax_get_catatan', [$this, 'get_notes']);
        add_action('wp_ajax_get_single_catatan', [$this, 'get_single_note']);
    }

    public function save_note()
    {
        check_ajax_referer('catatan-si-boy-nonce', 'security');

        $user_id = get_current_user_id();
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'content' => wp_kses_post($_POST['content']),
            'color' => sanitize_hex_color($_POST['color']),
            'shared_users' => isset($_POST['shared_users']) ?
                implode(',', array_map('intval', $_POST['shared_users'])) : ''
        ];

        global $wpdb;
        $table = $wpdb->prefix . 'catatan_si_boy';

        if (isset($_POST['note_id']) && !empty($_POST['note_id']))
        {
            // Update existing note
            $wpdb->update($table, $data, ['id' => intval($_POST['note_id']), 'user_id' => $user_id]);
        }
        else
        {
            // Create new note
            $data['user_id'] = $user_id;
            $wpdb->insert($table, $data);
        }

        wp_send_json_success();
    }

    public function delete_note()
    {
        check_ajax_referer('catatan-si-boy-nonce', 'security');

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'catatan_si_boy', [
            'id' => intval($_POST['note_id']),
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success();
    }

    public function get_notes()
    {
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'catatan_si_boy';

        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE user_id = %d 
            OR FIND_IN_SET(%d, shared_users) 
            ORDER BY created_at DESC",
            $user_id,
            $user_id
        ));

        wp_send_json_success($notes);
    }

    // Tambahkan method baru
    public function get_single_note()
    {
        check_ajax_referer('catatan-si-boy-nonce', 'security');

        $user_id = get_current_user_id();
        $note_id = intval($_POST['note_id']);

        global $wpdb;
        $table = $wpdb->prefix . 'catatan_si_boy';

        $note = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
        WHERE id = %d 
        AND (user_id = %d OR FIND_IN_SET(%d, shared_users))",
            $note_id,
            $user_id,
            $user_id
        ));

        if ($note)
        {
            $note->shared_users = explode(',', $note->shared_users);
            wp_send_json_success(['data' => $note]);
        }
        else
        {
            wp_send_json_error(['message' => 'Catatan tidak ditemukan']);
        }
    }
}
