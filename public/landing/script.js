const menuButton = document.querySelector('.menu-toggle');
const nav = document.querySelector('.main-nav');

menuButton.addEventListener('click', () => {
  const open = document.body.classList.toggle('menu-open');
  menuButton.setAttribute('aria-expanded', String(open));
});

nav.addEventListener('click', (event) => {
  if (event.target.matches('a')) {
    document.body.classList.remove('menu-open');
    menuButton.setAttribute('aria-expanded', 'false');
  }
});

const modal = document.querySelector('.demo-modal');
document.querySelector('[data-demo]').addEventListener('click', () => modal.showModal());
document.querySelector('.modal-close').addEventListener('click', () => modal.close());
document.querySelector('.modal-ok').addEventListener('click', () => modal.close());
modal.addEventListener('click', (event) => {
  const bounds = modal.getBoundingClientRect();
  const outside = event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
  if (outside) modal.close();
});

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));
