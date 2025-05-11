

// — Live Date / Day / Time —
function updateDateTime() {
  const now = new Date();
  const dateBox = document.getElementById('dateBox');
  const timeBox = document.getElementById('timeBox');

  if (dateBox) {
    const weekday = now.toLocaleDateString(undefined, { weekday: 'long' });
    const datePart = now.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      dateBox.textContent = `${weekday} · ${datePart}`;
  }

  if (timeBox) {
      let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds(), ampm = h >= 12 ? 'PM' : 'AM';
      h = h % 12 || 12;
      m = m < 10 ? '0' + m : m;
      s = s < 10 ? '0' + s : s;
      timeBox.textContent = `${h}:${m}:${s} ${ampm}`;
  }
}
setInterval(updateDateTime, 1000);
updateDateTime();


// — Sidebar Active Link —
const currentPath = window.location.pathname.split('/').pop();
const navLinks = document.querySelectorAll(".sidebar nav a");

navLinks.forEach(link => {
  const linkPath = link.getAttribute("href");
  if (linkPath === currentPath) {
      link.classList.add("active");
  } else {
      link.classList.remove("active");
  }
});

// — Logout Modal —
const logoutBtn = document.getElementById("logoutButton");
const logoutModal = document.getElementById("logoutModal");
const confirmLogout = document.getElementById("confirmLogout");
const cancelLogout = document.getElementById("cancelLogout");

if (logoutBtn && logoutModal) {
  logoutBtn.addEventListener("click", (e) => {
      e.preventDefault();
      logoutModal.style.display = "flex";
  });

  confirmLogout?.addEventListener("click", () => {
      window.location.href = "landing.html"; 
  });

  cancelLogout?.addEventListener("click", () => {
      logoutModal.style.display = "none";
  });
}