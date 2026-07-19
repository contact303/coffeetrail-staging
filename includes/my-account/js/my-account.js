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