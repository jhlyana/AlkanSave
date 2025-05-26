document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setupNavLinks();
    setupLogoutModal();
});

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
        let h = now.getHours(), 
            m = now.getMinutes(), 
            s = now.getSeconds(), 
            ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        m = m < 10 ? '0' + m : m;
        s = s < 10 ? '0' + s : s;
        timeBox.textContent = `${h}:${m}:${s} ${ampm}`;
    }
}

// Initialize and update time every second
setInterval(updateDateTime, 1000);
updateDateTime();

// — Sidebar Active Link —
function setupNavLinks() {
    const currentPath = window.location.pathname.replace('/AlkanSave/', '');
    const navLinks = document.querySelectorAll(".sidebar nav a");
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute("href");
        if (linkPath === currentPath) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });
}

// — Logout Modal —
function setupLogoutModal() {
    const logoutBtn = document.getElementById("logoutButton");
    const logoutModal = document.getElementById("logoutModal");
    const confirmLogout = document.getElementById("confirmLogout");
    const cancelLogout = document.getElementById("cancelLogout");

    if (logoutBtn && logoutModal) {
        // Open modal
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            logoutModal.style.display = "flex";
        });

        // Confirm logout
        confirmLogout?.addEventListener("click", () => {
            window.location.href = "/AlkanSave/landing.html"; 
        });

        // Cancel logout
        cancelLogout?.addEventListener("click", () => {
            logoutModal.style.display = "none";
        });

        // Close modal when clicking outside
        window.addEventListener("click", (e) => {
            if (e.target === logoutModal) {
                logoutModal.style.display = "none";
            }
        });
    }
}