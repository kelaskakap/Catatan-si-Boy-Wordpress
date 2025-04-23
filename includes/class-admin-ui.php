<?php
class CatatanSiBoy_Admin_UI
{
    public function __construct()
    {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }

    public function add_dashboard_widget()
    {
        wp_add_dashboard_widget(
            'catatan_si_boy_widget',
            'Catatan si Boy',
            [$this, 'render_widget']
        );
    }

    public function render_widget()
    {
        $users = get_users(['fields' => ['ID', 'display_name']]);
        $current_user = wp_get_current_user();
    ?>
        <div class="catatan-container">
            <div class="catatan-header" style="margin-bottom: 10px;">
                <button class="button button-primary add-note">+ Catatan Baru</button>
            </div>

            <div class="note-form" style="display:none;">
                <input type="hidden" class="note-id" value="">
                <input type="text" class="note-title" placeholder="Judul (opsional)">
                <div class="editor-container">
                    <?php
                    wp_editor('', 'catatancontent', [
                        'textarea_name' => 'catatancontent',
                        'textarea_rows' => 5,
                        'teeny' => true,
                        'media_buttons' => false,
                        'tinymce' => [
                            'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink',
                            'toolbar2' => ''
                        ]
                    ]);
                    ?>
                </div>
                <div class="note-meta">
                    <label for="note-color">Pilih warna</label>
                    <select class="note-color">
                        <option value="#fff9c4">Kuning</option>
                        <option value="#c8e6c9">Hijau</option>
                        <option value="#bbdefb">Biru</option>
                        <option value="#ffccbc">Oranye</option>
                        <option value="#f8bbd0">Merah Muda</option>
                    </select>

                    <label for="note-share">Bagikan ke</label>
                    <select class="note-share" multiple style="width:100%" placeholder="Bagikan ke...">
                        <?php foreach ($users as $user):
                            if ($user->ID != $current_user->ID): ?>
                                <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
                        <?php endif;
                        endforeach; ?>
                    </select>

                    <div class="note-actions">
                        <button class="button button-primary save-note">Simpan</button>
                        <button class="button button-secondary cancel-note">Batal</button>
                        <button class="button delete-theme delete-note" style="display: none;">Hapus</button>
                    </div>
                </div>
            </div>

            <div class="notes-list"></div>
        </div>
<?php
    }
}
