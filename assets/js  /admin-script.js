/**
 * QuickAccess WP Admin Scripts
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
            $(document).on('change', '.qaw-status-toggle', this.toggleSlug);
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

        // Copy URL with checkmark feedback
        copyUrl: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const url = $btn.data('url');

            if (!url) return;

            // Copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    QAW.showCopySuccess($btn);
                }).catch(function() {
                    QAW.fallbackCopy(url, $btn);
                });
            } else {
                QAW.fallbackCopy(url, $btn);
            }
        },

        fallbackCopy: function(url, $btn) {
            const $temp = $('<textarea>');
            $temp.val(url).css({ position: 'fixed', left: '-9999px' }).appendTo('body').select();
            
            try {
                document.execCommand('copy');
                QAW.showCopySuccess($btn);
            } catch (err) {
                QAW.toast('Failed to copy', 'error');
            }
            
            $temp.remove();
        },

        showCopySuccess: function($btn) {
            $btn.addClass('copied');
            QAW.toast(qawAdmin.i18n.copied, 'success');
            
            setTimeout(function() {
                $btn.removeClass('copied');
            }, 2000);
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

        // Toggle status via switch
        toggleSlug: function() {
            const $checkbox = $(this);
            const id = $checkbox.data('id');
            const isChecked = $checkbox.is(':checked');

            $checkbox.prop('disabled', true);

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_toggle_slug',
                    nonce: qawAdmin.nonce,
                    id: id,
                    active: isChecked ? 0 : 1
                },
                success: function(response) {
                    if (response.success) {
                        QAW.toast(response.data.message, 'success');
                    } else {
                        $checkbox.prop('checked', !isChecked);
                        QAW.toast(response.data.message, 'error');
                    }
                    $checkbox.prop('disabled', false);
                },
                error: function() {
                    $checkbox.prop('checked', !isChecked);
                    QAW.toast(qawAdmin.i18n.error, 'error');
                    $checkbox.prop('disabled', false);
                }
            });
        },

        // Generate slug - only icon spins
        generateSlug: function(e) {
            e.preventDefault();

            const $btn = $(this);
            
            if ($btn.hasClass('generating')) return;
            
            $btn.addClass('generating').prop('disabled', true);

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_generate_slug',
                    nonce: qawAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#qaw-slug').val(response.data.slug).trigger('input');
                        setTimeout(function() {
                            $('#qaw-slug').trigger('blur');
                        }, 100);
                    }
                },
                complete: function() {
                    $btn.removeClass('generating').prop('disabled', false);
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
                        $status.html('✓ ' + qawAdmin.i18n.available).addClass('available').removeClass('unavailable');
                    } else {
                        $status.html('✗ ' + qawAdmin.i18n.unavailable).addClass('unavailable').removeClass('available');
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
            $('.qaw-toast').remove();
            
            const $toast = $('<div class="qaw-toast ' + (type || '') + '">' + message + '</div>');
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
