// Global variables for charts
let categoryBarChart = null;
let goalDoughnutChart = null;
let currentSelectedMonth = null;
let currentYear = new Date().getFullYear();

// Base URL for all API requests - now using absolute path with origin
const BASE_URL = window.location.origin + '/AlkanSave/2_Application/controllers/reports_data.php';

// Initialize the reports page
document.addEventListener('DOMContentLoaded', function() {
    initializeReports();
    setupMonthClickHandlers();
});

/**
 * Initialize all report components
 */
async function initializeReports() {
    try {
        await Promise.all([
            loadSavingsGrowthChart(),
            loadGoalCompletionChart(),
            loadMonthsWithGoals(),
            loadCompletedGoals()
        ]);
    } catch (error) {
        console.error('Error initializing reports:', error);
        showErrorMessage('Failed to load reports data');
    }
}

/**
 * Load and display savings growth chart
 */
async function loadSavingsGrowthChart() {
    try {
        const response = await fetch(`${BASE_URL}?action=savings_growth`, {
            credentials: 'include' // Include cookies for session
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch savings growth data');
        }
        
        // Destroy existing chart if it exists
        if (categoryBarChart) {
            categoryBarChart.destroy();
        }
        
        // Create new chart
        const ctx = document.getElementById('categoryBarChart');
        if (!ctx) {
            throw new Error('Chart canvas not found');
        }
        
        categoryBarChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.categories.length > 0 ? data.categories : ['No Data'],
                datasets: [{
                    label: '₱ Saved',
                    data: data.amounts.length > 0 ? data.amounts : [0],
                    backgroundColor: '#fa8fbc',
                    borderColor: '#24336e',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 20,
                        bottom: 5
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#24336e',
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        ticks: {
                            color: '#24336e'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#24336e'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error loading savings growth chart:', error);
        showChartError('categoryBarChart', 'Failed to load savings data');
    }
}

/**
 * Load and display goal completion chart
 */
async function loadGoalCompletionChart() {
    try {
        const response = await fetch(`${BASE_URL}?action=goal_completion`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch goal completion data');
        }
        
        // Destroy existing chart if it exists
        if (goalDoughnutChart) {
            goalDoughnutChart.destroy();
        }
        
        // Create new chart
        const ctx = document.getElementById('goalDoughnutChart');
        if (!ctx) {
            throw new Error('Chart canvas not found');
        }
        
        const hasData = data.total > 0;
        const chartData = hasData ? [data.completed, data.remaining] : [1];
        const chartLabels = hasData ? ['Completed', 'Remaining'] : ['No Goals'];
        const chartColors = hasData ? ['#fa8fbc', '#ffd1dc'] : ['#e0e0e0'];
        
        goalDoughnutChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Goals',
                    data: chartData,
                    backgroundColor: chartColors,
                    borderColor: '#fff3f8',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 20,
                        bottom: 20
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#24336e'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (!hasData) return 'No goals created yet';
                                const label = context.label;
                                const value = context.parsed;
                                const total = data.total;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error loading goal completion chart:', error);
        showChartError('goalDoughnutChart', 'Failed to load goal completion data');
    }
}

/**
 * Load months that have completed goals and highlight them
 */
async function loadMonthsWithGoals() {
    try {
        const response = await fetch(`${BASE_URL}?action=months_with_goals&year=${currentYear}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch months data');
        }
        
        // Reset all month cards
        const monthCards = document.querySelectorAll('.month-card');
        monthCards.forEach(card => {
            card.classList.remove('active', 'has-goals');
            card.style.opacity = '0.5';
        });
        
        // Highlight months with completed goals
        data.months.forEach(monthData => {
            const monthName = monthData.monthName;
            const monthCard = document.querySelector(`[data-month="${monthName}"]`);
            if (monthCard) {
                monthCard.classList.add('has-goals');
                monthCard.style.opacity = '1';
                monthCard.title = `${monthData.goalCount} completed goal(s)`;
            }
        });
        
    } catch (error) {
        console.error('Error loading months with goals:', error);
        // Don't show error for this as it's not critical
    }
}

/**
 * Load completed goals based on current filters
 */
async function loadCompletedGoals() {
    try {
        let url = `${BASE_URL}?action=completed_goals&year=${currentYear}`;
        if (currentSelectedMonth) {
            url += `&month=${currentSelectedMonth}`;
        }
        
        const response = await fetch(url, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch completed goals');
        }
        
        displayCompletedGoals(data.goals);
        
    } catch (error) {
        console.error('Error loading completed goals:', error);
        showTableError('Failed to load completed goals');
    }
}

/**
 * Display completed goals in the table
 */
function displayCompletedGoals(goals) {
    const tbody = document.querySelector('.transaction-table tbody');
    if (!tbody) {
        console.error('Table body not found');
        return;
    }
    
    if (goals.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                    ${currentSelectedMonth ? 'No goals completed in the selected month' : 'No completed goals found'}
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = goals.map(goal => `
        <tr>
            <td>${formatDate(goal.CompletionDate)}</td>
            <td>${escapeHtml(goal.CategoryName || 'Uncategorized')}</td>
            <td>${escapeHtml(goal.GoalName)}</td>
            <td>₱${parseFloat(goal.SavedAmount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td>₱${parseFloat(goal.TargetAmount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}

/**
 * Setup click handlers for month cards
 */
function setupMonthClickHandlers() {
    const monthCards = document.querySelectorAll('.month-card');
    
    monthCards.forEach(card => {
        card.addEventListener('click', function() {
            const monthName = this.getAttribute('data-month');
            
            // Check if this month has goals
            if (!this.classList.contains('has-goals')) {
                return; // Don't allow selection of months without goals
            }
            
            // Toggle selection
            if (this.classList.contains('active')) {
                // Deselect current month
                this.classList.remove('active');
                currentSelectedMonth = null;
            } else {
                // Select new month
                monthCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                currentSelectedMonth = getMonthNumber(monthName);
            }
            
            // Reload completed goals with new filter
            loadCompletedGoals();
        });
    });
}

/**
 * Utility functions
 */
function getMonthNumber(monthName) {
    const months = {
        'January': 1, 'February': 2, 'March': 3, 'April': 4,
        'May': 5, 'June': 6, 'July': 7, 'August': 8,
        'September': 9, 'October': 10, 'November': 11, 'December': 12
    };
    return months[monthName];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showErrorMessage(message) {
    console.error(message);
    // You could implement a toast notification here
}

function showChartError(chartId, message) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        const parent = canvas.parentElement;
        parent.innerHTML = `
            <h2 class="section-title">${parent.querySelector('.section-title')?.textContent || 'Chart'}</h2>
            <div style="display: flex; align-items: center; justify-content: center; height: 300px; color: #666; text-align: center;">
                <div>
                    <p>${message}</p>
                    <button onclick="location.reload()" style="margin-top: 10px; padding: 8px 16px; background: #fa8fbc; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Retry
                    </button>
                </div>
            </div>
        `;
    }
}

function showTableError(message) {
    const tbody = document.querySelector('.transaction-table tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                    <p>${message}</p>
                    <button onclick="location.reload()" style="margin-top: 10px; padding: 8px 16px; background: #fa8fbc; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Retry
                    </button>
                </td>
            </tr>
        `;
    }
}