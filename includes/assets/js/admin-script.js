/**
 * QuickAccess WP Admin Scripts
 *
 * @package QuickAccessWP
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const QAW = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initSlugPreview();
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            // Copy URL button
            $(document).on('click', '.qaw-copy-btn', this.copyUrl);
            
            // Delete slug
            $(document).on('click', '.qaw-delete-btn', this.deleteSlug);
            
            // Toggle slug status
            $(document).on('click', '.qaw-toggle-btn', this.toggleSlug);
            
            // Generate random slug
            $('#generate-slug').on('click', this.generateSlug);
            
            // New slug form
            $('#qaw-new-slug-form').on('submit', this.createSlug);
            
            // Edit slug form
            $('#qaw-edit-slug-form').on('submit', this.updateSlug);
            
            // Slug input change for preview
            $('#slug').on('input', this.updateSlugPreview);
            
            // Check slug availability on blur
            $('#slug').on('blur', this.checkSlugAvailability);
        },

        /**
         * Initialize slug preview
         */
        initSlugPreview: function() {
            const $slug = $('#slug');
            if ($slug.length && $('#slug-preview').length) {
                this.updateSlugPreview();
            }
        },

        /**
         * Update slug preview
         */
        updateSlugPreview: function() {
            const slug = $('#slug').val() || 'your-slug';
            $('#slug-preview').text(slug);
            
            // Update edit URL preview if exists
            if ($('#edit-url-preview').length) {
                $('#edit-url-preview').text(qawAdmin.homeUrl + slug);
                $('.qaw-copy-btn').data('url', qawAdmin.homeUrl + slug);
            }
        },

        /**
         * Check slug availability
         */
        checkSlugAvailability: function() {
            const slug = $('#slug').val();
            const excludeId = $('#qaw-edit-slug-form').data('id') || 0;
            const $status = $('#slug-status');
            
            if (!slug || slug.length < 2) {
                $status.html('').removeClass('available unavailable');
                return;
            }
            
            $status.html('<span class="checking">' + qawAdmin.strings.checking + '</span>');
            
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
                    if (response.success) {
                        $status.html('<span class="available">✓ ' + response.data.message + '</span>')
                               .removeClass('unavailable').addClass('available');
                    } else {
                        $status.html('<span class="unavailable">✗ ' + response.data.message + '</span>')
                               .removeClass('available').addClass('unavailable');
                    }
                },
                error: function() {
                    $status.html('').removeClass('available unavailable');
                }
            });
        },

        /**
         * Copy URL to clipboard
         */
        copyUrl: function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    QAW.showNotice(qawAdmin.strings.copied, 'success');
                }).catch(function() {
                    QAW.fallbackCopy(url);
                });
            } else {
                QAW.fallbackCopy(url);
            }
        },

        /**
         * Fallback copy method
         */
        fallbackCopy: function(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                QAW.showNotice(qawAdmin.strings.copied, 'success');
            } catch (err) {
                QAW.showNotice(qawAdmin.strings.copyFailed, 'error');
            }
            
            document.body.removeChild(textarea);
        },

        /**
         * Delete slug
         */
        deleteSlug: function(e) {
            e.preventDefault();
            
            if (!confirm(qawAdmin.strings.confirmDelete)) {
                return;
            }
            
            const $btn = $(this);
            const id = $btn.data('id');
            const $row = $btn.closest('tr');
            const isEditPage = $btn.closest('.qaw-form').length > 0;
            
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
                        if (isEditPage) {
                            window.location.href = qawAdmin.adminUrl;
                        } else {
                            $row.fadeOut(300, function() {
                                $(this).remove();
                            });
                            QAW.showNotice(response.data.message, 'success');
                        }
                    } else {
                        QAW.showNotice(response.data.message, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    QAW.showNotice(qawAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Toggle slug status
         */
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
                        const newActive = response.data.is_active;
                        $btn.data('active', newActive);
                        
                        // Update icon
                        const $icon = $btn.find('.dashicons');
                        if (newActive) {
                            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                            $btn.attr('title', 'Disable');
                        } else {
                            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                            $btn.attr('title', 'Enable');
                        }
                        
                        // Update status badge
                        const $statusCell = $btn.closest('tr').find('.column-status');
                        if (newActive) {
                            $statusCell.html('<span class="qaw-badge qaw-badge-active">Active</span>');
                        } else {
                            $statusCell.html('<span class="qaw-badge qaw-badge-inactive">Disabled</span>');
                        }
                        
                        QAW.showNotice(response.data.message, 'success');
                    } else {
                        QAW.showNotice(response.data.message, 'error');
                    }
                    $btn.prop('disabled', false);
                },
                error: function() {
                    QAW.showNotice(qawAdmin.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Generate random slug
         */
        generateSlug: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $slug = $('#slug');
            
            $btn.prop('disabled', true).find('.dashicons').addClass('spin');
            
            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_generate_slug',
                    nonce: qawAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $slug.val(response.data.slug).trigger('input').trigger('blur');
                    }
                    $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
                },
                error: function() {
                    QAW.showNotice(qawAdmin.strings.error, 'error');
                    $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
                }
            });
        },

        /**
         * Create new slug
         */
        createSlug: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submit = $form.find('button[type="submit"]');
            const $message = $('#qaw-form-message');
            
            // Validate
            const slug = $('#slug').val();
            const userId = $('#user_id').val();
            
            if (!slug) {
                QAW.showFormMessage($message, qawAdmin.strings.enterSlug, 'error');
                return;
            }
            
            if (!userId) {
                QAW.showFormMessage($message, qawAdmin.strings.selectUser, 'error');
                return;
            }
            
            $submit.prop('disabled', true);
            
            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_create_slug',
                    nonce: qawAdmin.nonce,
                    slug: slug,
                    user_id: userId,
                    redirect_url: $('#redirect_url').val(),
                    max_uses: $('#max_uses').val(),
                    expires_at: $('#expires_at').val()
                },
                success: function(response) {
                    if (response.success) {
                        QAW.showFormMessage($message, response.data.message, 'success');
                        setTimeout(function() {
                            window.location.href = qawAdmin.adminUrl;
                        }, 1000);
                    } else {
                        QAW.showFormMessage($message, response.data.message, 'error');
                        $submit.prop('disabled', false);
                    }
                },
                error: function() {
                    QAW.showFormMessage($message, qawAdmin.strings.error, 'error');
                    $submit.prop('disabled', false);
                }
            });
        },

        /**
         * Update existing slug
         */
        updateSlug: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submit = $form.find('button[type="submit"]');
            const $message = $('#qaw-form-message');
            const id = $form.data('id');
            
            $submit.prop('disabled', true);
            
            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_update_slug',
                    nonce: qawAdmin.nonce,
                    id: id,
                    slug: $('#slug').val(),
                    user_id: $('#user_id').val(),
                    redirect_url: $('#redirect_url').val(),
                    max_uses: $('#max_uses').val(),
                    expires_at: $('#expires_at').val(),
                    is_active: $('input[name="is_active"]').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        QAW.showFormMessage($message, response.data.message, 'success');
                        
                        // Update copy button URL
                        if (response.data.url) {
                            $('.qaw-copy-btn').data('url', response.data.url);
                            $('#edit-url-preview').text(response.data.url);
                        }
                    } else {
                        QAW.showFormMessage($message, response.data.message, 'error');
                    }
                    $submit.prop('disabled', false);
                },
                error: function() {
                    QAW.showFormMessage($message, qawAdmin.strings.error, 'error');
                    $submit.prop('disabled', false);
                }
            });
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible qaw-notice"><p>' + message + '</p></div>');
            
            $('.qaw-wrap h1').first().after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Show form message
         */
        showFormMessage: function($container, message, type) {
            $container.html('<div class="notice notice-' + type + '"><p>' + message + '</p></div>');
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 300);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        QAW.init();
    });

})(jQuery);
