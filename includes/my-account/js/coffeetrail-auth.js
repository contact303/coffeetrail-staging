document.addEventListener('DOMContentLoaded', function () {
  const authRoot = document.querySelector('.ct-auth');

  if (!authRoot) {
    return;
  }

  function activateMainForm(formName) {
    const activeTab = authRoot.querySelector(
      '.ct-auth__main-tabs [data-form="' + formName + '"]'
    );

    const heading = authRoot.querySelector('[data-auth-heading]');

    authRoot
      .querySelectorAll('.ct-auth__main-tabs [data-form]')
      .forEach(function (tab) {
        const isActive = tab.dataset.form === formName;

        tab.parentElement.classList.toggle('active', isActive);
        tab.setAttribute(
          'aria-selected',
          isActive ? 'true' : 'false'
        );
      });

    authRoot
      .querySelectorAll('[data-auth-form]')
      .forEach(function (panel) {
        panel.classList.toggle(
          'hide',
          panel.dataset.authForm !== formName
        );
      });

    authRoot
      .querySelectorAll('[data-auth-switch-copy]')
      .forEach(function (copy) {
        copy.hidden =
          copy.dataset.authSwitchCopy !== formName;
      });

    if (activeTab && heading) {
      heading.textContent = activeTab.dataset.authTitle;
    }
  }

  authRoot
    .querySelectorAll('[data-auth-switch]')
    .forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();

        activateMainForm(link.dataset.authSwitch);
      });
    });

  authRoot
    .querySelectorAll('.ct-auth__main-tabs [data-form]')
    .forEach(function (tab) {
      tab.addEventListener('click', function (event) {
        event.preventDefault();

        activateMainForm(tab.dataset.form);
      });
    });

  authRoot
    .querySelectorAll('[data-auth-methods]')
    .forEach(function (methods) {
      methods
        .querySelectorAll('[data-auth-method]')
        .forEach(function (tab) {
          tab.addEventListener('click', function () {
            const method = tab.dataset.authMethod;

            methods
              .querySelectorAll('[data-auth-method]')
              .forEach(function (item) {
                const isActive =
                  item.dataset.authMethod === method;

                item.classList.toggle(
                  'is-active',
                  isActive
                );

                item.setAttribute(
                  'aria-selected',
                  isActive ? 'true' : 'false'
                );
              });

            methods
              .querySelectorAll('[data-auth-method-panel]')
              .forEach(function (panel) {
                const isActive =
                  panel.dataset.authMethodPanel === method;

                panel.classList.toggle(
                  'is-active',
                  isActive
                );

                panel.hidden = !isActive;
              });
          });
        });
    });

  const metaSelectors = [
    '[data-provider="facebook"]',
    '[data-provider="meta"]',
    '.nsl-button-facebook',
    '.mo-openid-app-icons a[href*="facebook.com"]',
    'a[href*="facebook.com/dialog/oauth"]'
  ];

  authRoot
    .querySelectorAll(metaSelectors.join(','))
    .forEach(function (provider) {
      const providerRow =
        provider.closest(
          '.nsl-button-wrapper, li, .mo-openid-app-icons, .social-login-button'
        ) || provider;

      providerRow.remove();
    });
});

// Handle OTP registration form submission
// Handle OTP registration form submission
document.addEventListener('submit', async (event) => {
    const form = event.target.closest(
        '#ct-form-email-register, #ct-form-otp, #ct-form-otp-resend'
    );

    if (!form) {
        return;
    }

    event.preventDefault();

    const container = form.closest('.ct-email-register');

    if (!container) {
        return;
    }

    const formData = new FormData(form);

    formData.append('action', 'ct_register_otp');

    container.classList.add('is-loading');

    try {
        const response = await fetch(ctAuth.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        console.log('OTP response:', result);

        if (!result.success) {
            throw new Error(
                result.data?.message || 'Registration request failed'
            );
        }

        if (result.data?.redirect) {
            window.location.assign(result.data.redirect);
            return;
        }

        if (result.data?.html) {
            container.outerHTML = result.data.html;
            initOtpResendTimer();
        }

    } catch (error) {
        console.error('OTP registration error:', error);

    } finally {
        const newContainer = document.querySelector('.ct-email-register');

        if (newContainer) {
            newContainer.classList.remove('is-loading');
        }
    }
});

// Initialize OTP resend timer
function initOtpResendTimer(scope = document) {
    const button = scope.querySelector('#ct-otp-resend-btn');
    const timer = scope.querySelector('#ct-otp-resend-timer');

    if (!button || !timer) {
        return;
    }

    // מונע הפעלה כפולה.
    if (button.dataset.timerInitialized === '1') {
        return;
    }

    button.dataset.timerInitialized = '1';

    let seconds = 30;

    button.disabled = true;
    timer.textContent = `(${seconds})`;

    const interval = setInterval(() => {
        seconds--;

        if (seconds > 0) {
            timer.textContent = `(${seconds})`;
            return;
        }

        clearInterval(interval);

        timer.textContent = '';
        button.disabled = false;
        button.removeAttribute('disabled');
    }, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
    initOtpResendTimer();
});