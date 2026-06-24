/**
 * ct-auth.js
 * Handles interactivity on the custom CoffeeTrail registration page.
 */

(function ($) {
    'use strict';

    // ─────────────────────────────────────────────────────────────────────
    // Inline field error helpers
    // ─────────────────────────────────────────────────────────────────────

    function setFieldError($field, message) {
        $field.addClass('ct-field--error').removeClass('ct-field--valid');
        var $group = $field.closest('.ct-field-group');
        $group.find('.ct-field-error').remove();
        if (message) {
            $group.append('<span class="ct-field-error">' + message + '</span>');
        }
    }

    function clearFieldError($field) {
        $field.removeClass('ct-field--error');
        $field.closest('.ct-field-group').find('.ct-field-error').remove();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Auto-clear error when the user starts typing
    // ─────────────────────────────────────────────────────────────────────

    $(document).on('input change', '#ct-auth-manual .ct-field, #ct-auth-gmail .ct-field, #ct-auth-login .ct-field', function () {
        var $el = $(this);
        if ($el.hasClass('ct-field--error') && $el.val().trim()) {
            clearFieldError($el);
        }
    });

    // ─────────────────────────────────────────────────────────────────────
    // Email valid-state on blur
    // ─────────────────────────────────────────────────────────────────────

    $(document).on('blur', '#ct-auth-manual input[type="email"].ct-field, #ct-auth-gmail input[type="email"].ct-field, #ct-auth-login input[type="email"].ct-field', function () {
        var $el  = $(this);
        var val  = $el.val().trim();
        if (!val) { return; }
        if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            $el.removeClass('ct-field--error').addClass('ct-field--valid');
            clearFieldError($el);
        }
    });

    // ─────────────────────────────────────────────────────────────────────
    // Password visibility toggle
    // ─────────────────────────────────────────────────────────────────────

    $(document).on('click', '.ct-password-toggle', function () {
        var $btn   = $(this);
        var $input = $btn.closest('.ct-field-group').find('.ct-field[type="password"], .ct-field[type="text"]');
        if (!$input.length) { return; }

        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');

        // Toggle eye icon title / aria
        $btn.attr('aria-label', isPassword ? 'הסתר סיסמה' : 'הצג סיסמה');
    });

    // ─────────────────────────────────────────────────────────────────────
    // Variant toggle (Gmail ↔ Manual)
    // ─────────────────────────────────────────────────────────────────────

    function showVariant(id) {
        $('.ct-auth-variant').removeClass('ct-auth-variant--active');
        $('#' + id).addClass('ct-auth-variant--active');
    }

    $(document).on('click', '[data-ct-switch-variant]', function (e) {
        e.preventDefault();
        var target = $(this).data('ct-switch-variant');
        if (target) { showVariant(target); }
    });

    // ─────────────────────────────────────────────────────────────────────
    // Manual form client-side validation
    // ─────────────────────────────────────────────────────────────────────

    $(document).on('submit', '#ct-form-manual', function (e) {
        var valid = true;

        var $firstName = $(this).find('[name="first_name"]');
        if (!$firstName.val().trim()) {
            setFieldError($firstName, 'יש להזין שם פרטי');
            valid = false;
        }

        var $lastName = $(this).find('[name="last_name"]');
        if (!$lastName.val().trim()) {
            setFieldError($lastName, 'יש להזין שם משפחה');
            valid = false;
        }

        var $email = $(this).find('[name="email"]');
        var emailVal = $email.val().trim();
        if (!emailVal) {
            setFieldError($email, 'יש להזין כתובת אימייל');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            setFieldError($email, 'כתובת האימייל אינה תקינה');
            valid = false;
        }

        var $password = $(this).find('[name="password"]');
        if ($password.val().length < 8) {
            setFieldError($password, 'הסיסמה חייבת להכיל לפחות 8 תווים');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            // Scroll to first error
            var $firstError = $(this).find('.ct-field--error').first();
            if ($firstError.length) {
                $firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $firstError.focus();
            }
        }
    });

    // ─────────────────────────────────────────────────────────────────────
    // Gmail / email-only form validation
    // ─────────────────────────────────────────────────────────────────────

    $(document).on('submit', '#ct-form-gmail', function (e) {
        var $email = $(this).find('[name="email"]');
        var emailVal = $email.val().trim();

        if (!emailVal) {
            setFieldError($email, 'יש להזין כתובת אימייל');
            e.preventDefault();
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            setFieldError($email, 'כתובת האימייל אינה תקינה');
            e.preventDefault();
        }
    });

    // ─────────────────────────────────────────────────────────────────────
    // Login form validation
    // ─────────────────────────────────────────────────────────────────────

    $(document).on('submit', '#ct-form-login', function (e) {
        var valid = true;

        var $email   = $(this).find('[name="email"]');
        var emailVal = $email.val().trim();
        if (!emailVal) {
            setFieldError($email, 'יש להזין כתובת אימייל');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            setFieldError($email, 'כתובת האימייל אינה תקינה');
            valid = false;
        }

        var $password = $(this).find('[name="password"]');
        if (!$password.val()) {
            setFieldError($password, 'יש להזין סיסמה');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            var $firstError = $(this).find('.ct-field--error').first();
            if ($firstError.length) {
                $firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $firstError.focus();
            }
        }
    });

}(jQuery));

/* OTP resend timer. */
(function ($) {
    'use strict';

    $(function () {
        var $button = $('#ct-otp-resend-btn');
        var $timer = $('#ct-otp-resend-timer');

        if (!$button.length || !$timer.length) {
            return;
        }

        var seconds = 30;
        $button.prop('disabled', true);

        var interval = window.setInterval(function () {
            seconds -= 1;
            $timer.text('(' + seconds + ')');

            if (seconds <= 0) {
                window.clearInterval(interval);
                $timer.text('');
                $button.prop('disabled', false);
            }
        }, 1000);
    });
}(jQuery));
