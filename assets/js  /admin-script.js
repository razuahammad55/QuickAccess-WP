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
            // Copy URL with icon change
            $(document).on('click', '.qaw-copy-btn', this.copyUrl);
            
            // Delete slug
            $(document).on('click', '.qaw-delete-btn', this.deleteSlug);
            
            // Table toggle (new toggle switch)
            $(document).on('change', '.qaw-table-toggle input', this.toggleSlug);
            
            // Generate slug - only spin icon
            $(document).on('click', '#qaw-generate-slug', this.generateSlug);
            
            // Form submit
            $(document).on('submit', '#qaw-form', this.submitForm);
            
            // Slug preview
            $(document).on('input', '#qaw-slug', this.updatePreview);
            
            // Check slug availability
            $(document).on('blur', '#qaw-slug', this.checkSlug);
        },

        initSlugPreview: function() {
            this.updatePreview();
        },

        updatePreview: function() {
            const slug = $('#qaw-slug').val() || 'your-slug';
            $('#slug-preview').text(slug);
        },

        // IMPROVED: Copy with icon change to checkmark
        copyUrl: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const url = $btn.data('url');

            // Store original icon
            const originalIcon = $btn.html();
            
            // Checkmark icon
            const checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';

            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    // Change to checkmark and add success class
                    $btn.html(checkIcon).addClass('copied');
                    QAW.toast(qawAdmin.i18n.copied, 'success');
                    
                    // Revert after 2 seconds
                    setTimeout(function() {
                        $btn.html(originalIcon).removeClass('copied');
                    }, 2000);
                });
            } else {
                // Fallback for older browsers
                const temp = $('<input>').val(url).appendTo('body').select();
                document.execCommand('copy');
                temp.remove();
                
                $btn.html(checkIcon).addClass('copied');
                QAW.toast(qawAdmin.i18n.copied, 'success');
                
                setTimeout(function() {
                    $btn.html(originalIcon).removeClass('copied');
                }, 2000);
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

        // UPDATED: Toggle using checkbox change event
        toggleSlug: function(e) {
            const $checkbox = $(this);
            const $toggle = $checkbox.closest('.qaw-table-toggle');
            const id = $toggle.data('id');
            const newActive = $checkbox.is(':checked') ? 1 : 0;

            $checkbox.prop('disabled', true);

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_toggle_slug',
                    nonce: qawAdmin.nonce,
                    id: id,
                    active: newActive ? 0 : 1 // Send opposite because we want to toggle
                },
                success: function(response) {
                    if (response.success) {
                        QAW.toast(response.data.message, 'success');
                        // Update the badge in the status column
                        const $row = $toggle.closest('tr');
                        const $badge = $row.find('.column-status .qaw-badge');
                        if (response.data.is_active) {
                            $badge.removeClass('qaw-badge-inactive').addClass('qaw-badge-active').text('Active');
                        } else {
                            $badge.removeClass('qaw-badge-active').addClass('qaw-badge-inactive').text('Disabled');
                        }
                    } else {
                        // Revert checkbox
                        $checkbox.prop('checked', !newActive);
                        QAW.toast(response.data.message, 'error');
                    }
                    $checkbox.prop('disabled', false);
                },
                error: function() {
                    // Revert checkbox
                    $checkbox.prop('checked', !newActive);
                    QAW.toast(qawAdmin.i18n.error, 'error');
                    $checkbox.prop('disabled', false);
                }
            });
        },

        // UPDATED: Only spin the SVG icon, not the whole button
        generateSlug: function(e) {
            e.preventDefault();

            const $btn = $(this);
            
            // Add generating class to spin only the icon
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
                        $('#qaw-slug').val(response.data.slug).trigger('input').trigger('blur');
                    }
                    $btn.removeClass('generating').prop('disabled', false);
                },
                error: function() {
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

            $status.html('<span class="checking">⏳ Checking...</span>').removeClass('available unavailable').addClass('checking');

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
                        $status.html('<span class="available">✓ ' + qawAdmin.i18n.available + '</span>').addClass('available');
                    } else {
                        $status.html('<span class="unavailable">✗ ' + qawAdmin.i18n.unavailable + '</span>').addClass('unavailable');
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
            // Remove existing toasts
            $('.qaw-toast').remove();
            
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
