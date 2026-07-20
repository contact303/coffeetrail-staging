document.addEventListener('click', async (event) => {
    const button = event.target.closest('.link_copy');

    if (!button) {
        return;
    }

    const url = button.dataset.url;

    if (!url) {
        return;
    }

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(url);
        } else {
            fallbackCopyText(url);
        }

        const originalText = button.querySelector('span').textContent;

        button.querySelector('span').textContent = 'הקישור הועתק';

        setTimeout(() => {
            button.querySelector('span').textContent = originalText;
        }, 2000);
    } catch (error) {
        console.error('Copy failed:', error);
    }
});

// Fallback method for copying text to clipboard
function fallbackCopyText(text) {
    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';

    document.body.appendChild(textarea);

    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    const copied = document.execCommand('copy');

    document.body.removeChild(textarea);

    if (!copied) {
        throw new Error('Copy command failed');
    }
}

// Handle the help panel toggle
document.addEventListener('DOMContentLoaded', () => {
    const help = document.querySelector('.ct-account-help');

    if (!help) {
        return;
    }

    const toggle = help.querySelector('.ct-account-help__toggle');
    const panel = help.querySelector('.ct-account-help__panel');

    if (!toggle || !panel) {
        return;
    }

    const openPanel = () => {
        panel.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    };

    const closePanel = () => {
        panel.setAttribute('hidden', '');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();

        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    panel.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', closePanel);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePanel();
            toggle.focus();
        }
    });
});