document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('.toc-sidebar');
  const overlay = document.querySelector('.toc-overlay');
  const toggleButtons = document.querySelectorAll('[data-toggle="toc"]');
  const scrollIndicator = document.querySelector('.scroll-indicator');
  const nav = document.querySelector('.nav');
  const sectionElements = document.querySelectorAll('.section');

  function toggleTOC() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }

  toggleButtons.forEach((button) => {
    button.addEventListener('click', toggleTOC);
  });

  overlay?.addEventListener('click', toggleTOC);

  scrollIndicator?.addEventListener('click', () => {
    nav?.scrollIntoView({ behavior: 'smooth' });
  });

  document.querySelectorAll('.toc-list a').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const targetId = link.getAttribute('href');
      const targetElement = document.querySelector(targetId);

      if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }

      if (sidebar.classList.contains('active')) {
        toggleTOC();
      }
    });
  });

  const observer = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          obs.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: '0px 0px -100px 0px'
    }
  );

  sectionElements.forEach((section) => observer.observe(section));
});
