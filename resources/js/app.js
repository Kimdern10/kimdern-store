/**
 * Small touches for the shop: scroll-reveal animations, the sticky header shadow
 * and number count-ups. Runs again after Livewire updates the page.
 */
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function revealOnScroll() {
    const items = document.querySelectorAll('[data-reveal]:not(.is-visible)');

    if (reducedMotion || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    items.forEach((el) => {
        if (el.dataset.reveal) {
            el.style.setProperty('--reveal-delay', `${Number(el.dataset.reveal) || 0}ms`);
        }
        observer.observe(el);
    });

    // Safety net: whatever is already on screen shows even if the observer is slow to report.
    setTimeout(() => {
        items.forEach((el) => {
            if (!el.classList.contains('is-visible') && el.getBoundingClientRect().top < window.innerHeight) {
                el.classList.add('is-visible');
                observer.unobserve(el);
            }
        });
    }, 600);
}

function countUp() {
    document.querySelectorAll('[data-count]:not([data-counted])').forEach((el) => {
        el.dataset.counted = '1';
        const target = Number(el.dataset.count);
        const suffix = el.dataset.suffix || '';

        if (reducedMotion) {
            el.textContent = target.toLocaleString() + suffix;
            return;
        }

        const start = performance.now();
        const step = (now) => {
            const p = Math.min((now - start) / 1400, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString() + suffix;
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });
}

let scrollListening = false;

function stickyHeader() {
    // The header is replaced on every wire:navigate page change, so look it up each time.
    const update = () => document.querySelector('[data-header]')?.classList.toggle('is-scrolled', window.scrollY > 8);
    update();

    if (!scrollListening) {
        window.addEventListener('scroll', update, { passive: true });
        scrollListening = true;
    }
}

function boot() {
    document.documentElement.classList.add('js');
    revealOnScroll();
    countUp();
    stickyHeader();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
document.addEventListener('livewire:init', () => {
    // Livewire rewrites class lists when it updates a component: keep things that were already shown visible.
    window.Livewire.hook('morph.updating', ({ el, toEl }) => {
        if (el.classList?.contains('is-visible') && toEl.classList) {
            toEl.classList.add('is-visible');
        }
    });

    // New product cards arrive when filters change: reveal them too.
    window.Livewire.hook('morph.updated', () => requestAnimationFrame(revealOnScroll));
});
