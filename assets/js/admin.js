jQuery(document).ready(function ($)
{
    // Initialize TinyMCE editor when ready
    function initializeEditor()
    {
        if (typeof tinymce !== 'undefined' && tinymce.get('catatancontent')) {
            return tinymce.get('catatancontent');
        }
        return null;
    }

    let editor = null;
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function (e)
        {
            if (e.editor.id === 'catatancontent') {
                editor = e.editor;
                attachNoteEvents();
            }
        });
    } else {
        console.warn('TinyMCE is not available.');
    }

    // Pre-fetch users
    let cachedUsers = null;
    function fetchAllUsers()
    {
        if (cachedUsers) {
            return Promise.resolve(cachedUsers);
        }
        return $.ajax({
            url: '/wp-admin/admin-ajax.php',
            method: 'POST',
            data: { action: 'get_users' },
            dataType: 'json'
        }).then(users =>
        {
            cachedUsers = users;
            return users;
        }).catch(error =>
        {
            console.error('Error fetching users:', error);
            return [];
        });
    }

    // Toggle form
    $('.add-note').off('click').click(function ()
    {
        resetForm();
        $('.note-form').slideDown();
    });

    $('.cancel-note').off('click').click(function ()
    {
        $('.note-form').slideUp();
        resetForm();
    });

    // Reset form
    function resetForm()
    {
        $('.note-id').val('');
        $('.note-title').val('');
        $('.note-color').val('#fff9c4');
        $('.note-share').val(null).trigger('change');
        $('.save-note').text('Simpan');
        $('.delete-note').hide();
        if (editor) {
            editor.setContent('');
        } else {
            $('#catatancontent').val('');
        }
    }

    // Load notes
    function loadNotes()
    {
        $.ajax({
            url: catatanSiBoy.ajax_url,
            type: 'POST',
            data: {
                action: 'get_catatan',
                security: catatanSiBoy.nonce
            },
            success: function (response)
            {
                if (response.success) {
                    renderNotes(response.data);
                } else {
                    console.error('Failed to load notes:', response);
                }
            },
            error: function (xhr, status, error)
            {
                console.error('AJAX error:', error);
            }
        });
    }

    // Render notes
    function renderNotes(notes)
    {
        fetchAllUsers().then(users =>
        {
            var html = '';

            $.each(notes, function (index, note)
            {
                var sharedWith = '';
                if (note.shared_users) {
                    // Handle shared_users as string or array
                    var sharedUsers = typeof note.shared_users === 'string'
                        ? note.shared_users.split(',').map(id => id.trim())
                        : Array.isArray(note.shared_users)
                            ? note.shared_users.map(String)
                            : [];
                    sharedUsers.forEach(function (userId)
                    {
                        var user = users.find(u => u.ID == userId);
                        if (user) {
                            sharedWith += user.display_name + ', ';
                        }
                    });
                    sharedWith = sharedWith.slice(0, -2); // Remove trailing comma and space
                }

                html += `
                    <div class="note" style="background:${note.color}" data-id="${note.id}">
                        <div class="note-title">${note.title || 'Tanpa Judul'}</div>
                        <div class="note-content">${note.content}</div>
                        <div class="note-meta">
                            Dibuat: ${new Date(note.created_at).toLocaleString()}
                            ${sharedWith ? '<br>Dibagikan ke: ' + sharedWith : ''}
                        </div>
                        <div class="note-actions">
                            <a class="edit-note" data-id="${note.id}">Edit</a>
                            <a class="delete-note" data-id="${note.id}">Hapus</a>
                        </div>
                    </div>
                `;
            });

            $('.notes-list').html(html || '<p>Belum ada catatan. Tambahkan catatan baru!</p>');
            attachNoteEvents();
        }).catch(error =>
        {
            console.error('Error rendering notes:', error);
            $('.notes-list').html('<p>Gagal memuat catatan.</p>');
        });
    }

    // Get user by ID (async, using cached users)
    function getUserById(userId)
    {
        return fetchAllUsers().then(users =>
        {
            return users.find(user => user.ID == userId) || null;
        });
    }

    // Attach events to notes
    function attachNoteEvents()
    {
        $('.edit-note').off('click').click(function (e)
        {
            e.preventDefault();
            var noteId = $(this).data('id');
            console.log('Edit note clicked, noteId:', noteId);

            $.ajax({
                url: catatanSiBoy.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_single_catatan',
                    note_id: noteId,
                    security: catatanSiBoy.nonce
                },
                success: function (response)
                {
                    console.log('AJAX response:', response);
                    if (response.success && response.data && response.data.data) {
                        var note = response.data.data;
                        console.log('Note data:', note);

                        $('.note-id').val(note.id || '');
                        $('.note-title').val(note.title || '');
                        $('.note-color').val(note.color || '#fff9c4');

                        if (note.shared_users) {
                            var sharedUsers = typeof note.shared_users === 'string'
                                ? note.shared_users.split(',').map(id => id.trim())
                                : Array.isArray(note.shared_users)
                                    ? note.shared_users.map(String)
                                    : [];
                            $('.note-share').val(sharedUsers).trigger('change');
                            console.log('Shared users set:', sharedUsers);
                        } else {
                            $('.note-share').val(null).trigger('change');
                            console.log('Shared users cleared');
                        }

                        if (editor) {
                            editor.setContent(note.content || '');
                            console.log('TinyMCE content set:', note.content);
                        } else {
                            $('#catatancontent').val(note.content || '');
                            console.log('Textarea content set:', note.content);
                        }

                        $('.save-note').text('Update');
                        $('.delete-note').show().data('id', note.id);
                        $('.note-form').slideDown();
                    } else {
                        console.error('Invalid response:', response);
                        alert('Gagal memuat catatan. Silakan coba lagi.');
                    }
                },
                error: function (xhr, status, error)
                {
                    console.error('AJAX error:', status, error);
                    alert('Terjadi kesalahan saat memuat catatan.');
                }
            });
        });

        $('.delete-note').off('click').click(function (e)
        {
            e.preventDefault();
            if (confirm('Yakin ingin menghapus catatan ini?')) {
                var noteId = $(this).data('id');
                console.log('Delete note clicked, noteId:', noteId);

                $.ajax({
                    url: catatanSiBoy.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_catatan',
                        note_id: noteId,
                        security: catatanSiBoy.nonce
                    },
                    success: function (response)
                    {
                        console.log('Delete response:', response);
                        loadNotes();
                        $('.note-form').slideUp();
                        resetForm();
                    },
                    error: function (xhr, status, error)
                    {
                        console.error('AJAX error:', status, error);
                        alert('Terjadi kesalahan saat menghapus catatan.');
                    }
                });
            }
        });
    }

    // Save note
    $('.save-note').off('click').click(function ()
    {
        var content = editor ? editor.getContent() : $('#catatancontent').val();

        var data = {
            action: 'save_catatan',
            security: catatanSiBoy.nonce,
            note_id: $('.note-id').val(),
            title: $('.note-title').val(),
            content: content,
            color: $('.note-color').val(),
            shared_users: $('.note-share').val()
        };

        $.ajax({
            url: catatanSiBoy.ajax_url,
            type: 'POST',
            data: data,
            success: function ()
            {
                loadNotes();
                $('.note-form').slideUp();
                resetForm();
            },
            error: function (xhr, status, error)
            {
                console.error('AJAX error:', error);
            }
        });
    });

    // Initial load
    loadNotes();
});