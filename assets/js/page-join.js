document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.ct-join [data-counter]');

    if (!counters.length) {
        return;
    }

    const formatNumber = (value, decimals) => {
        return new Intl.NumberFormat('he-IL', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(value);
    };

    const animateCounter = (element) => {
        if (element.dataset.animated === 'true') {
            return;
        }

        element.dataset.animated = 'true';

        const target = Number.parseFloat(element.dataset.value || '0');
        const duration = Number.parseInt(element.dataset.duration || '1600', 10);
        const prefix = element.dataset.prefix || '';
        const suffix = element.dataset.suffix || '';
        const decimals = String(target).includes('.') ? String(target).split('.')[1].length : 0;
        const startTime = performance.now();

        const tick = (currentTime) => {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;

            element.textContent = `${prefix}${formatNumber(current, progress === 1 ? decimals : 0)}${suffix}`;

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    };

    if (!('IntersectionObserver' in window)) {
        counters.forEach(animateCounter);
        return;
    }

    const observer = new IntersectionObserver((entries, instance) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            animateCounter(entry.target);
            instance.unobserve(entry.target);
        });
    }, {
        threshold: 0.35,
    });

    counters.forEach((counter) => observer.observe(counter));
});


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-testimonial-track]').forEach((track) => {
        const cards = Array.from(
            track.querySelectorAll('[data-testimonial-card]')
        );

        const slider = track.closest('.ct-join__testimonial-slider');
        const section = track.closest('.ct-join__testimonials');

        const dotsContainer = slider?.querySelector(
            '[data-testimonial-dots]'
        );

        const prevButton = section?.querySelector(
            '[data-testimonial-prev]'
        );

        const nextButton = section?.querySelector(
            '[data-testimonial-next]'
        );

        if (!cards.length || !dotsContainer) {
            return;
        }

        let positions = [];
        let activeIndex = 0;
        let scrollTimer;

        const getCardStep = () => {
            if (cards.length < 2) {
                return cards[0].offsetWidth;
            }

            return Math.abs(
                cards[1].offsetLeft - cards[0].offsetLeft
            );
        };

        const buildPositions = () => {
            const step = getCardStep();
            const maxScroll = Math.max(
                0,
                track.scrollWidth - track.clientWidth
            );

            positions = [0];

            for (
                let position = step;
                position < maxScroll;
                position += step
            ) {
                positions.push(position);
            }

            if (
                maxScroll > 0 &&
                positions[positions.length - 1] !== maxScroll
            ) {
                positions.push(maxScroll);
            }
        };

        const buildDots = () => {
            dotsContainer.innerHTML = '';

            positions.forEach((position, index) => {
                const dot = document.createElement('button');

                dot.type = 'button';
                dot.className = 'ct-join__testimonial-dot';
                dot.setAttribute(
                    'aria-label',
                    `מעבר לקבוצת המלצות ${index + 1}`
                );

                dot.addEventListener('click', () => {
                    goTo(index);
                });

                dotsContainer.appendChild(dot);
            });
        };

        const updateControls = () => {
            dotsContainer
                .querySelectorAll('.ct-join__testimonial-dot')
                .forEach((dot, index) => {
                    const isActive = index === activeIndex;

                    dot.classList.toggle('is-active', isActive);
                    dot.setAttribute(
                        'aria-current',
                        isActive ? 'true' : 'false'
                    );
                });

            if (prevButton) {
                prevButton.disabled = activeIndex === 0;
            }

            if (nextButton) {
                nextButton.disabled =
                    activeIndex === positions.length - 1;
            }
        };

        const goTo = (index) => {
            activeIndex = Math.max(
                0,
                Math.min(index, positions.length - 1)
            );

            /*
             * במסילת RTL ערך scrollLeft משתנה בין דפדפנים,
             * לכן משתמשים בכרטיס הראשון וב-scrollBy.
             */
            const targetPosition = positions[activeIndex];
            const currentPosition = Math.abs(track.scrollLeft);

            track.scrollBy({
                left: -(targetPosition - currentPosition),
                behavior: 'smooth',
            });

            updateControls();
        };

        const findActivePosition = () => {
            const currentScroll = Math.abs(track.scrollLeft);

            let closestIndex = 0;
            let closestDistance = Infinity;

            positions.forEach((position, index) => {
                const distance = Math.abs(
                    currentScroll - position
                );

                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestIndex = index;
                }
            });

            activeIndex = closestIndex;
            updateControls();
        };

        prevButton?.addEventListener('click', () => {
            goTo(activeIndex - 1);
        });

        nextButton?.addEventListener('click', () => {
            goTo(activeIndex + 1);
        });

        track.addEventListener(
            'scroll',
            () => {
                window.clearTimeout(scrollTimer);

                scrollTimer = window.setTimeout(
                    findActivePosition,
                    80
                );
            },
            { passive: true }
        );

        const rebuild = () => {
            buildPositions();
            buildDots();
            activeIndex = Math.min(
                activeIndex,
                positions.length - 1
            );
            updateControls();
        };

        rebuild();

        window.addEventListener('resize', rebuild);
    });
});

const revealSections = document.querySelectorAll(
    '.ct-join__hero, .ct-join__section, .ct-join__final-cta'
);

if (revealSections.length) {
    if (
        !('IntersectionObserver' in window) ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        revealSections.forEach((section) => {
            section.classList.add('is-visible');
        });
    } else {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            },
            {
                threshold: 0.12,
                rootMargin: '0px 0px -8% 0px',
            }
        );

        revealSections.forEach((section) => {
            revealObserver.observe(section);
        });
    }
}

// Reveal steps section
const steps = document.querySelector('.ct-join__steps');

if (steps) {
    const stepItems = Array.from(
        steps.querySelectorAll('.ct-join__step')
    );

    const lineDuration = 1200;

    const activateSteps = () => {
        steps.classList.add('is-active');

        stepItems.forEach((step, index) => {
            const delay = stepItems.length > 1
                ? (lineDuration / (stepItems.length - 1)) * index
                : 0;

            window.setTimeout(() => {
                step.classList.add('is-active');
            }, delay);
        });
    };

    const observer = new IntersectionObserver(
        (entries, instance) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                activateSteps();
                instance.unobserve(entry.target);
            });
        },
        {
            threshold: 0.4,
        }
    );

    observer.observe(steps);
}

document.addEventListener('DOMContentLoaded', () => {
    const card = document.querySelector(
        '.ct-join__benefit-grid .ct-join__benefit-card:nth-child(3)'
    );

    if (!card) {
        return;
    }

    const wrap = card.querySelector('.ct-join__benefit-image-wrap');
    const image = card.querySelector('.ct-join__benefit-image');

    if (!wrap || !image) {
        return;
    }

    const startImageScroll = () => {
        image.style.transform = 'translate3d(0, 0, 0)';

        requestAnimationFrame(() => {
            const imageHeight = image.getBoundingClientRect().height;
            const wrapHeight = wrap.getBoundingClientRect().height;
            const distance = Math.max(0, imageHeight - wrapHeight);

            console.log({
                imageHeight,
                wrapHeight,
                distance,
            });

            if (distance <= 0) {
                return;
            }

            requestAnimationFrame(() => {
                image.style.transform =
                    `translate3d(0, -${distance}px, 0)`;
            });
        });
    };

    const observeCard = () => {
        if (!('IntersectionObserver' in window)) {
            startImageScroll();
            return;
        }

        const observer = new IntersectionObserver(
            (entries, instance) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    startImageScroll();
                    instance.unobserve(entry.target);
                });
            },
            {
                threshold: 0.25,
            }
        );

        observer.observe(card);
    };

    if (image.complete) {
        observeCard();
    } else {
        image.addEventListener('load', observeCard, {
            once: true,
        });
    }

console.log({
    imageHeight: image.getBoundingClientRect().height,
    wrapHeight: wrap.getBoundingClientRect().height,
    distance:
        image.getBoundingClientRect().height -
        wrap.getBoundingClientRect().height
});    
});