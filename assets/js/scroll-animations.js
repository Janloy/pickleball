document.addEventListener('DOMContentLoaded', () => {
  const animatedElements = document.querySelectorAll('.js-scroll-animate');

  if (!animatedElements.length || !('IntersectionObserver' in window)) {
    animatedElements.forEach((el) => el.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        obs.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.2,
  });

  animatedElements.forEach((el) => observer.observe(el));
});
