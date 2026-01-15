/**
 * QuickAccess WP Admin Scripts
 *
 * @package QuickAccessWP
 * @version 1.0.0
 */

(function($) {
    'use strict';

    const QAW = {
        init: function() {
            this.bindEvents();
            this.initSlugPreview();
        },

        bindEvents: function() {
            $(document).on('click', '.qaw-copy-btn', this.copyUrl);
            $(document).on('click', '.qaw-delete-btn', this.deleteSlug);
            $(document).on('click', '.qaw-toggle-btn', this.toggleSlug);
            $(document).on('click', '#qaw-generate-slug', this.generateSlug);
            $(document).on('submit', '#qaw-form', this.submitForm);
            $(document).on('input', '#qaw-slug', this.updatePreview);
            $(document).on('blur', '#qaw-slug', this.checkSlug);
        },

        initSlugPreview: function() {
            this.updatePreview();
        },

        updatePreview: function() {
            const slug = $('#qaw-slug').val() || 'your-slug';
            $('#slug-preview').text(slug);
        },

        copyUrl: function(e) {
            e.preventDefault();
            const url = $(this).data('url');

            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    QAW.toast(qawAdmin.i18n.copied, 'success');
                });
            } else {
                const temp = $('<input>').val(url).appendTo('body').select();
                document.execCommand('copy');
                temp.remove();
                QAW.toast(qawAdmin.i18n.copied, 'success');
            }
        },

        deleteSlug: function(e) {
            e.preventDefault();

            if (!confirm(qawAdmin.i18n.confirmDelete)) {
                return;
            }

            const $btn = $(this);
            const id = $btn.data('id');
            const $row = $btn.closest('tr');

            $btn.prop('disabled', true);

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_delete_slug',
                    nonce: qawAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        QAW.toast(response.data.message, 'success');
                    } else {
                        QAW.toast(response.data.message, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    QAW.toast(qawAdmin.i18n.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        toggleSlug: function(e) {
            e.preventDefault();

            const $btn = $(this);
            const id = $btn.data('id');
            const active = $btn.data('active');

            $btn.prop('disabled', true);

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_toggle_slug',
                    nonce: qawAdmin.nonce,
                    id: id,
                    active: active
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        QAW.toast(response.data.message, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    QAW.toast(qawAdmin.i18n.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        generateSlug: function(e) {
            e.preventDefault();

            const $btn = $(this);
            $btn.prop('disabled', true).addClass('qaw-spin');

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_generate_slug',
                    nonce: qawAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#qaw-slug').val(response.data.slug).trigger('input').trigger('blur');
                    }
                    $btn.prop('disabled', false).removeClass('qaw-spin');
                },
                error: function() {
                    $btn.prop('disabled', false).removeClass('qaw-spin');
                }
            });
        },

        checkSlug: function() {
            const slug = $(this).val();
            const excludeId = $('#qaw-form').data('id') || 0;
            const $status = $('#slug-status');

            if (!slug || slug.length < 2) {
                $status.html('').removeClass('available unavailable checking');
                return;
            }

            $status.html('Checking...').removeClass('available unavailable').addClass('checking');

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_check_slug',
                    nonce: qawAdmin.nonce,
                    slug: slug,
                    exclude_id: excludeId
                },
                success: function(response) {
                    $status.removeClass('checking');
                    if (response.success && response.data.available) {
                        $status.html('✓ ' + qawAdmin.i18n.available).addClass('available');
                    } else {
                        $status.html('✗ ' + qawAdmin.i18n.unavailable).addClass('unavailable');
                    }
                },
                error: function() {
                    $status.html('').removeClass('available unavailable checking');
                }
            });
        },

        submitForm: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submit = $form.find('button[type="submit"]');
            const id = $form.data('id');
            const action = id ? 'qaw_update_slug' : 'qaw_create_slug';

            $submit.prop('disabled', true);

            const data = {
                action: action,
                nonce: qawAdmin.nonce,
                slug: $('#qaw-slug').val(),
                user_id: $('#qaw-user').val(),
                redirect_url: $('#qaw-redirect').val(),
                max_uses: $('#qaw-max-uses').val(),
                expires_at: $('#qaw-expires').val()
            };

            if (id) {
                data.id = id;
                data.is_active = $('#qaw-active').is(':checked') ? 1 : 0;
            }

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        QAW.toast(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.href = qawAdmin.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=quickaccess-wp');
                        }, 1000);
                    } else {
                        QAW.toast(response.data.message, 'error');
                        $submit.prop('disabled', false);
                    }
                },
                error: function() {
                    QAW.toast(qawAdmin.i18n.error, 'error');
                    $submit.prop('disabled', false);
                }
            });
        },

        toast: function(message, type) {
            const $toast = $('<div class="qaw-toast ' + type + '">' + message + '</div>');
            $('body').append($toast);
            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        QAW.init();
    });

})(jQuery);
