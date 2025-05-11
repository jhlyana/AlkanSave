document.addEventListener('DOMContentLoaded', () => {
    const monthCards = document.querySelectorAll('.month-card');
  
    monthCards.forEach(card => {
      card.addEventListener('click', () => {
        monthCards.forEach(c => c.classList.remove('active'));
        card.classList.add('active');
      });
    });
  });
  