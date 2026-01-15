/**
 * QuickAccess WP Admin Scripts
 * @package QuickAccessWP
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // SVG Icons
    var copyIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>';
    var checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';

    var QAW = {
        
        init: function() {
            this.bindEvents();
            this.initSlugPreview();
        },

        bindEvents: function() {
            // Copy URL
            $(document).on('click', '.qaw-copy-btn', this.copyUrl);
            
            // Delete
            $(document).on('click', '.qaw-delete-btn', this.deleteSlug);
            
            // Toggle status via AJAX
            $(document).on('change', '.qaw-status-toggle', this.toggleStatus);
            
            // Generate slug
            $(document).on('click', '#qaw-generate-slug', this.generateSlug);
            
            // Form submit
            $(document).on('submit', '#qaw-form', this.submitForm);
            
            // Slug preview
            $(document).on('input', '#qaw-slug', this.updatePreview);
            
            // Check slug
            $(document).on('blur', '#qaw-slug', this.checkSlug);
        },

        initSlugPreview: function() {
            var $slug = $('#qaw-slug');
            if ($slug.length) {
                $('#slug-preview').text($slug.val() || 'your-slug');
            }
        },

        updatePreview: function() {
            var slug = $(this).val() || 'your-slug';
            $('#slug-preview').text(slug);
        },

        /**
         * Copy URL and swap icon
         */
        copyUrl: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var url = $btn.data('url');

            if (!url || $btn.hasClass('copied')) {
                return;
            }

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
            var $temp = $('<textarea>');
            $temp.val(url).css({position: 'fixed', left: '-9999px'}).appendTo('body').select();
            
            try {
                document.execCommand('copy');
                QAW.showCopySuccess($btn);
            } catch (err) {
                QAW.toast('Failed to copy', 'error');
            }
            
            $temp.remove();
        },

        showCopySuccess: function($btn) {
            // Store original HTML
            var originalHtml = $btn.html();
            
            // Swap to check icon and add class
            $btn.html(checkIcon).addClass('copied');
            
            // Show toast
            QAW.toast(qawAdmin.i18n.copied, 'success');
            
            // Revert after 1.5 seconds
            setTimeout(function() {
                $btn.html(originalHtml).removeClass('copied');
            }, 1500);
        },

        /**
         * Delete slug
         */
        deleteSlug: function(e) {
            e.preventDefault();

            if (!confirm(qawAdmin.i18n.confirmDelete)) {
                return;
            }

            var $btn = $(this);
            var id = $btn.data('id');
            var $row = $btn.closest('tr');

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

        /**
         * Toggle status via AJAX
         */
        toggleStatus: function() {
            var $checkbox = $(this);
            var id = $checkbox.data('id');
            var isNowChecked = $checkbox.is(':checked');
            var $switch = $checkbox.closest('.qaw-switch');

            // Disable while processing
            $checkbox.prop('disabled', true);
            $switch.css('opacity', '0.5');

            $.ajax({
                url: qawAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qaw_toggle_slug',
                    nonce: qawAdmin.nonce,
                    id: id,
                    active: isNowChecked ? 0 : 1 // Send opposite to toggle
                },
                success: function(response) {
                    if (response.success) {
                        QAW.toast(response.data.message, 'success');
                    } else {
                        // Revert on error
                        $checkbox.prop('checked', !isNowChecked);
                        QAW.toast(response.data.message, 'error');
                    }
                },
                error: function() {
                    // Revert on error
                    $checkbox.prop('checked', !isNowChecked);
                    QAW.toast(qawAdmin.i18n.error, 'error');
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                    $switch.css('opacity', '1');
                }
            });
        },

        /**
         * Generate slug - ONLY icon spins
         */
        generateSlug: function(e) {
            e.preventDefault();

            var $btn = $(this);
            
            if ($btn.hasClass('generating') || $btn.prop('disabled')) {
                return;
            }
            
            // Add class - CSS will spin only the .qaw-icon-spin element
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

        /**
         * Check slug availability
         */
        checkSlug: function() {
            var slug = $(this).val();
            var excludeId = $('#qaw-form').data('id') || 0;
            var $status = $('#slug-status');

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

        /**
         * Submit form
         */
        submitForm: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var id = $form.data('id');
            var action = id ? 'qaw_update_slug' : 'qaw_create_slug';

            $submit.prop('disabled', true);

            var data = {
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

        /**
         * Show toast notification
         */
        toast: function(message, type) {
            $('.qaw-toast').remove();
            
            var $toast = $('<div class="qaw-toast ' + (type || '') + '">' + message + '</div>');
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
