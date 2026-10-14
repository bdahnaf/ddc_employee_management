// DDC Workforce — homepage behaviour (no dependencies)
// Header state, mobile menu, gentle scroll reveal, active menu link,
// and the profile card switching between Project and Head Office.

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ---- Header ----
const header = document.querySelector('[data-header]');
const burger = document.querySelector('[data-burger]');

const onScroll = () => header?.classList.toggle('is-scrolled', window.scrollY > 10);
onScroll();
window.addEventListener('scroll', onScroll, { passive: true });

burger?.addEventListener('click', () => {
    const open = header.classList.toggle('menu-open');
    burger.setAttribute('aria-expanded', String(open));
});
document.querySelectorAll('[data-menu] a').forEach((a) =>
    a.addEventListener('click', () => {
        header.classList.remove('menu-open');
        burger?.setAttribute('aria-expanded', 'false');
    }),
);

// ---- Reveal on scroll (once) ----
const targets = document.querySelectorAll('.reveal, [data-steps]');
if (reduceMotion || !('IntersectionObserver' in window)) {
    targets.forEach((el) => el.classList.add('is-visible'));
} else {
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    targets.forEach((el) => io.observe(el));
}

// ---- Active menu link ----
const links = [...document.querySelectorAll('[data-menu] a[href^="#"]')];
const sections = links.map((a) => document.querySelector(a.getAttribute('href'))).filter(Boolean);
if ('IntersectionObserver' in window && sections.length) {
    const spy = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                links.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === `#${entry.target.id}`));
            }
        });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach((s) => spy.observe(s));
}

// ---- Profile card: Project <-> Head Office ----
const profile = document.querySelector('.profile');
if (profile && !reduceMotion) {
    const badge = profile.querySelector('[data-badge]');
    const place = profile.querySelector('[data-place]');
    let hq = false;
    setInterval(() => {
        hq = !hq;
        profile.classList.add('is-switching');
        setTimeout(() => {
            profile.classList.toggle('is-hq', hq);
            badge.textContent = hq ? 'HQ' : 'Project';
            place.textContent = hq ? 'Head Office' : 'Project site';
            profile.classList.remove('is-switching');
        }, 300);
    }, 4500);
}
