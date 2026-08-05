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

// Handle the listings panel toggle
document.addEventListener('DOMContentLoaded', () => {
    const help = document.querySelector('.ct-public-preview__title-row');

    if (!help) {
        return;
    }

    const toggle = help.querySelector('.ct-public-preview__title-toggle');
    const panel = help.querySelector('.ct-user-listings');

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

// Handle the WhatsApp share button click
document.addEventListener('click', (event) => {
    const button = event.target.closest(
        '.ct-dashboard-welcome .share-buttons button.share-button.whatsapp'
    );

    if (!button) {
        return;
    }

    const listingUrl = button.dataset.url;

    if (!listingUrl) {
        return;
    }

    const message = `עגלת קפה:\n${listingUrl}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;

    window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
});

// Handle the Facebook share button click
document.addEventListener('click', (event) => {
    const button = event.target.closest(
        '.ct-dashboard-welcome .share-buttons button.share-button.facebook'
    );

    if (!button) {
        return;
    }

    const listingUrl = button.dataset.url;

    if (!listingUrl) {
        return;
    }

    const facebookShareUrl =
        `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(listingUrl)}`;

    window.open(
        facebookShareUrl,
        'facebook-share',
        'width=680,height=520,noopener,noreferrer'
    );
});

// Handle the copy link button click
document.addEventListener('click', async (event) => {
    const button = event.target.closest(
        '.ct-dashboard-welcome .share-buttons button.share-button.copy-link'
    );

    if (!button) {
        return;
    }

    const listingUrl = button.dataset.url;

    if (!listingUrl) {
        return;
    }

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(listingUrl);
        } else {
            fallbackCopyText(listingUrl);
        }

        const originalText = button.textContent;

        button.textContent = 'הקישור הועתק';

        setTimeout(() => {
            button.textContent = originalText;
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

// Handle the share button toggle
document.addEventListener('click', (event) => {
    const button = event.target.closest('.ct-dashboard-welcome__share-button');

    if (!button) return;

    button.parentElement.classList.toggle('is-active');
});

// Handle the Pango modal functionality
document.addEventListener('DOMContentLoaded', () => {
    const openButton = document.querySelector(
        'button.ct-pango-benefits__button'
    );

    const infoModal = document.querySelector(
        '.ct-pango-modal--info'
    );

    const successModal = document.querySelector(
        '.ct-pango-modal--success'
    );

    if (!openButton || !infoModal || !successModal) {
        return;
    }

    let lastFocusedElement = null;

    /**
     * פתיחת פופאפ.
     */
    const openModal = (modal) => {
        lastFocusedElement = document.activeElement;

        modal.hidden = false;
        document.body.classList.add('ct-pango-modal-open');

        const focusTarget = modal.querySelector(
            '.ct-pango-modal__close, button, a[href]'
        );

        if (focusTarget) {
            focusTarget.focus();
        }
    };

    /**
     * סגירת פופאפ.
     */
    const closeModal = (modal) => {
        modal.hidden = true;

        if (infoModal.hidden && successModal.hidden) {
            document.body.classList.remove(
                'ct-pango-modal-open'
            );

            if (lastFocusedElement instanceof HTMLElement) {
                lastFocusedElement.focus();
            }
        }
    };

    /**
     * מעבר מפופאפ אחד לאחר.
     */
    const switchModal = (fromModal, toModal) => {
        fromModal.hidden = true;
        toModal.hidden = false;

        const focusTarget = toModal.querySelector(
            '.ct-pango-modal__close, button'
        );

        if (focusTarget) {
            focusTarget.focus();
        }
    };

    /**
     * פתיחת הפופאפ הראשי.
     */
    openButton.addEventListener('click', () => {
        openModal(infoModal);
    });

    /**
     * סגירה באמצעות כפתורי X.
     */
    document
        .querySelectorAll('.ct-pango-modal__close')
        .forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest(
                    '.ct-pango-modal'
                );

                if (modal) {
                    closeModal(modal);
                }
            });
        });

    /**
     * כפתור "אחזור לזה אחר כך".
     */
    const laterButton = infoModal.querySelector(
        '.ct-pango-modal__later'
    );

    if (laterButton) {
        laterButton.addEventListener('click', () => {
            closeModal(infoModal);
        });
    }

    /**
     * אישור ההצטרפות ומעבר לפופאפ ההצלחה.
     */
    const confirmButton = infoModal.querySelector(
        '.ct-pango-modal__confirm'
    );

    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            switchModal(infoModal, successModal);
        });
    }

    /**
     * סגירת פופאפ ההצלחה.
     */
    const successButton = successModal.querySelector(
        '.ct-pango-success__button'
    );

    if (successButton) {
        successButton.addEventListener('click', () => {
            closeModal(successModal);
        });
    }

    /**
     * סגירה בלחיצה על הרקע שמחוץ לחלון.
     */
    document
        .querySelectorAll('.ct-pango-modal')
        .forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });

    /**
     * העתקת קוד העגלה.
     */
    const copyButton = infoModal.querySelector(
        '.ct-pango-modal__copy-button'
    );

    const codeElement = infoModal.querySelector(
        '.ct-pango-modal__code'
    );

    if (copyButton && codeElement) {
        copyButton.addEventListener('click', async () => {
            const code = codeElement.textContent.trim();

            if (!code) {
                return;
            }

            const originalHtml = copyButton.innerHTML;

            try {
                if (
                    navigator.clipboard &&
                    window.isSecureContext
                ) {
                    await navigator.clipboard.writeText(code);
                } else {
                    const textarea =
                        document.createElement('textarea');

                    textarea.value = code;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'fixed';
                    textarea.style.top = '-9999px';
                    textarea.style.opacity = '0';

                    document.body.appendChild(textarea);

                    textarea.select();
                    textarea.setSelectionRange(
                        0,
                        textarea.value.length
                    );

                    document.execCommand('copy');

                    textarea.remove();
                }

                copyButton.textContent = 'הועתק!';
            } catch (error) {
                console.error(
                    'Pango code copy failed:',
                    error
                );

                copyButton.textContent = 'העתקה נכשלה';
            }

            window.setTimeout(() => {
                copyButton.innerHTML = originalHtml;
            }, 1800);
        });
    }

    /**
     * סגירה באמצעות מקש Escape.
     */
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (!successModal.hidden) {
            closeModal(successModal);
            return;
        }

        if (!infoModal.hidden) {
            closeModal(infoModal);
        }
    });
});

// Handle the benefit toggle functionality  
document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll(
            '.ct-growth-card button.ct-growth-card__action'
        )
        .forEach((toggleButton) => {
            const card = toggleButton.closest('.ct-growth-card');

            if (!card) {
                return;
            }

            const toggleContent = card.querySelector(
                '.ct-benefit-toggle'
            );

            if (!toggleContent) {
                return;
            }

            toggleButton.addEventListener('click', () => {
                const isHidden =
                    toggleContent.hasAttribute('hidden');

                toggleContent.toggleAttribute('hidden');

                toggleButton.setAttribute(
                    'aria-expanded',
                    isHidden ? 'true' : 'false'
                );
            });
        });
});