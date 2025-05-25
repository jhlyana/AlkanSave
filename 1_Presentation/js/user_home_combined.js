// === COMBINED SCRIPT: Date/Time + Logout + Dashboard ===

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('COMBINED SCRIPT: DOM loaded');
    
    // Initialize all functions
    initDateTime();
    initSidebar();
    initLogout();
    initDashboard();
});

// === DATE/TIME FUNCTIONS ===
function initDateTime() {
    console.log('COMBINED SCRIPT: Initializing date/time');
    updateDateTime();
    setInterval(updateDateTime, 1000);
}

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

// === SIDEBAR FUNCTIONS ===
function initSidebar() {
    console.log('COMBINED SCRIPT: Initializing sidebar');
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
}

// === LOGOUT FUNCTIONS ===
function initLogout() {
    console.log('COMBINED SCRIPT: Initializing logout');
    const logoutBtn = document.getElementById("logoutButton");
    const logoutModal = document.getElementById("logoutModal");
    const confirmLogout = document.getElementById("confirmLogout");
    const cancelLogout = document.getElementById("cancelLogout");

    if (logoutBtn && logoutModal) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            logoutModal.style.display = "flex";
        });

        if (confirmLogout) {
            confirmLogout.addEventListener("click", () => {
                window.location.href = "landing.html";
            });
        }

        if (cancelLogout) {
            cancelLogout.addEventListener("click", () => {
                logoutModal.style.display = "none";
            });
        }
    }
}

// === DASHBOARD FUNCTIONS ===
function initDashboard() {
    console.log('COMBINED SCRIPT: Initializing dashboard');
    
    // Wait a bit for everything to be ready
    setTimeout(() => {
        loadDashboardData();
        generateCalendar();
        setDailyQuote();
    }, 1000);
}

function loadDashboardData() {
    console.log('COMBINED SCRIPT: Starting dashboard data load...');
    
    fetch('/AlkanSave/2_Application/controllers/DashboardController.php?action=getDashboardData')
        .then(response => {
            console.log('COMBINED SCRIPT: Response received', response.status);
            return response.text();
        })
        .then(text => {
            console.log('COMBINED SCRIPT: Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('COMBINED SCRIPT: JSON parse error', e);
                showError('Invalid server response');
                return;
            }
            
            console.log('COMBINED SCRIPT: Parsed data:', data);
            
            if (!data.success) {
                console.error('COMBINED SCRIPT: Server error', data.error);
                showError(data.error || 'Server error');
                return;
            }

            // Update welcome message
            const userName = document.getElementById('userName');
            if (userName && data.userData && data.userData.FirstName) {
                userName.textContent = data.userData.FirstName + '!';
                console.log('COMBINED SCRIPT: Updated username to', data.userData.FirstName);
            }

            // Update progress rings
            setProgress(data.savingsProgress || 0, 'savingsCircle', 'savingsText');
            setProgress(data.goalProgress || 0, 'goalsCircle', 'goalsText');

            // Update total saved
            const totalSavedElement = document.getElementById('totalSavedAmount');
            if (totalSavedElement) {
                const amount = parseFloat(data.totalSaved || 0);
                totalSavedElement.textContent = '₱' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                console.log('COMBINED SCRIPT: Updated total saved to', amount);
            }

            // Update goals deadlines
            updateGoalsDeadlines(data.upcomingDeadlines || []);

            console.log('COMBINED SCRIPT: Dashboard update completed successfully!');
        })
        .catch(error => {
            console.error('COMBINED SCRIPT: Fetch error', error);
            showError('Failed to connect to server');
        });
}

function setProgress(percent, circleId, textId) {
    console.log('COMBINED SCRIPT: Setting progress', circleId, percent + '%');
    
    const circle = document.getElementById(circleId);
    const text = document.getElementById(textId);
    
    if (!circle || !text) {
        console.error('COMBINED SCRIPT: Progress elements not found', circleId, textId);
        return;
    }
    
    const radius = circle.r.baseVal.value;
    const circumference = 2 * Math.PI * radius;

    circle.style.strokeDasharray = circumference;
    const offset = circumference - (percent / 100) * circumference;
    circle.style.strokeDashoffset = offset;

    text.textContent = Math.round(percent) + '%';
}

function updateGoalsDeadlines(deadlines) {
    const goalsContainer = document.getElementById('goalsDeadlineContainer');
    if (!goalsContainer) {
        console.error('COMBINED SCRIPT: Goals container not found');
        return;
    }
    
    goalsContainer.innerHTML = '';

    if (deadlines.length === 0) {
        const noGoalsEntry = document.createElement('div');
        noGoalsEntry.className = 'goal-entry';
        noGoalsEntry.innerHTML = '<span>No upcoming deadlines</span><span>-</span>';
        goalsContainer.appendChild(noGoalsEntry);
        console.log('COMBINED SCRIPT: No upcoming deadlines');
    } else {
        console.log('COMBINED SCRIPT: Adding', deadlines.length, 'deadlines');
        deadlines.forEach(goal => {
            const goalEntry = document.createElement('div');
            goalEntry.className = 'goal-entry';
            
            const date = new Date(goal.TargetDate);
            const formattedDate = String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(date.getDate()).padStart(2, '0') + '-' + 
                                date.getFullYear();
            
            goalEntry.innerHTML = `<span>${goal.GoalName}</span><span>${formattedDate}</span>`;
            goalsContainer.appendChild(goalEntry);
        });
    }
}

function showError(message) {
    console.error('COMBINED SCRIPT: Showing error:', message);
    
    const userName = document.getElementById('userName');
    if (userName) {
        userName.textContent = 'Error!';
        userName.style.color = 'red';
    }
    
    const goalsContainer = document.getElementById('goalsDeadlineContainer');
    if (goalsContainer) {
        goalsContainer.innerHTML = `<div class="goal-entry"><span>${message}</span><span>!</span></div>`;
    }
}

function generateCalendar() {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();

    const monthNames = [
        "January", "February", "March", "April", "May", "June", 
        "July", "August", "September", "October", "November", "December"
    ];

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();

    const calendar = document.getElementById('calendarTable');
    const monthYearTitle = document.getElementById('calendarMonthYear');
    
    if (!calendar || !monthYearTitle) {
        console.error('COMBINED SCRIPT: Calendar elements not found');
        return;
    }
    
    calendar.innerHTML = '';
    monthYearTitle.innerText = `${monthNames[currentMonth]} ${currentYear}`;

    const weekdays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
    let headerRow = "<tr>";
    weekdays.forEach(day => {
        headerRow += `<th>${day}</th>`;
    });
    headerRow += "</tr>";
    calendar.innerHTML += headerRow;

    let day = 1;
    for (let i = 0; i < 6; i++) {
        let row = "<tr>";
        for (let j = 0; j < 7; j++) {
            if ((i === 0 && j < firstDay) || day > totalDays) {
                row += "<td></td>";
            } else {
                const isToday = day === today.getDate();
                row += `<td class="${isToday ? 'today' : ''}">${day}</td>`;
                day++;
            }
        }
        row += "</tr>";
        calendar.innerHTML += row;
        
        if (day > totalDays) break;
    }
    
    console.log('COMBINED SCRIPT: Calendar generated');
}

const dailyQuotes = [
    "Save money and money will save you.",
    "A penny saved is a penny earned.",
    "Do something today that your future self will thank you for.",
    "Small steps every day lead to big savings.",
    "Don't go broke trying to look rich.",
    "The habit of saving is itself an education.",
    "It's not how much money you make, but how much money you keep.",
    "Every peso saved is a peso earned.",
    "Financial peace isn't the acquisition of stuff. It's learning to live on less than you make.",
    "Beware of little expenses. A small leak will sink a great ship."
];

function setDailyQuote() {
    const today = new Date();
    const quoteIndex = today.getDate() % dailyQuotes.length;
    const quoteElement = document.getElementById("dailyQuote");
    if (quoteElement) {
        quoteElement.textContent = `"${dailyQuotes[quoteIndex]}"`;
        console.log('COMBINED SCRIPT: Daily quote set');
    }
}