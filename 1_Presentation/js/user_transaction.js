document.addEventListener('DOMContentLoaded', () => {
    const monthCards = document.querySelectorAll('.month-card');
    const transactionTableBody = document.querySelector('.transaction-table tbody');
    const currentYear = new Date().getFullYear();
    let selectedMonth = null;
    let monthlyStats = {};

    // Initialize the page
    init();

    async function init() {
        try {
            await loadMonthlyStats();
            await loadAllTransactions();
        } catch (error) {
            console.error('Error initializing transaction page:', error);
            showError('Failed to load transaction data');
        }
    }

    // Load monthly statistics to show which months have transactions
    async function loadMonthlyStats() {
        try {
            const response = await fetch(`../2_Application/controllers/TransactionController.php?action=getMonthlyStats&year=${currentYear}`);
            const data = await response.json();

            if (data.success) {
                monthlyStats = data.monthly_stats;
                updateMonthCardStyles();
            } else {
                console.error('Failed to load monthly stats:', data.error);
            }
        } catch (error) {
            console.error('Error loading monthly stats:', error);
        }
    }

    // Load all transactions (no month filter)
    async function loadAllTransactions() {
        try {
            showLoading();
            const response = await fetch(`../2_Application/controllers/TransactionController.php?action=getTransactions&year=${currentYear}`);
            const data = await response.json();

            if (data.success) {
                displayTransactions(data.transactions);
                // Reset selected month and remove all active classes
                selectedMonth = null;
                monthCards.forEach(c => c.classList.remove('active'));
            } else {
                throw new Error(data.error || 'Failed to load transactions');
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
            showError('Failed to load transactions');
        }
    }

    // Load transactions for a specific month
    async function loadMonthTransactions(monthNumber) {
        try {
            const monthName = getMonthName(monthNumber);
            showLoading(monthName);
            const response = await fetch(`../2_Application/controllers/TransactionController.php?action=getTransactions&month=${monthNumber}&year=${currentYear}`);
            const data = await response.json();

            if (data.success) {
                displayTransactions(data.transactions);
                if (data.transactions.length === 0) {
                    showNoTransactions(monthName);
                }
            } else {
                throw new Error(data.error || 'Failed to load month transactions');
            }
        } catch (error) {
            console.error('Error loading month transactions:', error);
            showError('Failed to load transactions for selected month');
        }
    }

    // Display transactions in the table
    function displayTransactions(transactions) {
        if (!transactionTableBody) {
            console.error('Transaction table body not found');
            return;
        }

        if (transactions.length === 0) {
            showNoTransactions();
            return;
        }

        // Clear existing rows
        transactionTableBody.innerHTML = '';

        // Add transaction rows
        transactions.forEach(transaction => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${transaction.formatted_date}</td>
                <td>${transaction.category}</td>
                <td>${transaction.goal}</td>
                <td>${transaction.formatted_amount}</td>
            `;
            transactionTableBody.appendChild(row);
        });
    }

    // Show no transactions message
    function showNoTransactions(monthName = null) {
        transactionTableBody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px; color: #666;">
                    <div>
                        <p style="margin: 0; font-size: 1.1rem;">📊 No transactions found${monthName ? ` for ${monthName}` : ''}</p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9rem;">Start saving to see your transaction history!</p>
                    </div>
                </td>
            </tr>
        `;
    }

    // Show loading state
    function showLoading(monthName = null) {
        const loadingText = monthName ? `Loading transactions for ${monthName}...` : 'Loading transactions...';
        transactionTableBody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px; color: #666;">
                    <div>
                        <p style="margin: 0; font-size: 1.1rem;">⏳ ${loadingText}</p>
                    </div>
                </td>
            </tr>
        `;
    }

    // Show error message
    function showError(message) {
        transactionTableBody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px; color: #e74c3c;">
                    <div>
                        <p style="margin: 0; font-size: 1.1rem;">❌ ${message}</p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9rem;">Please try refreshing the page.</p>
                    </div>
                </td>
            </tr>
        `;
    }

    // Update month card styles
    function updateMonthCardStyles() {
        monthCards.forEach((card, index) => {
            const monthNumber = index + 1;
            const monthStats = monthlyStats[monthNumber];
            
            if (monthStats && monthStats.count > 0) {
                card.title = `${monthStats.count} transactions - ₱${monthStats.total.toFixed(2)}`;
            } else {
                card.title = 'No transactions this month';
            }
        });
    }

    // Add click event listeners to month cards
    monthCards.forEach((card, index) => {
        card.addEventListener('click', async () => {
            const monthNumber = index + 1;
            const monthStats = monthlyStats[monthNumber];

            // Remove active class from all cards
            monthCards.forEach(c => c.classList.remove('active'));
            
            if (selectedMonth === monthNumber) {
                // If clicking the same month, show all transactions
                selectedMonth = null;
                await loadAllTransactions();
            } else {
                // Set active month and add active class
                selectedMonth = monthNumber;
                card.classList.add('active');
                
                if (monthStats && monthStats.count > 0) {
                    // Load transactions for selected month (has transactions)
                    await loadMonthTransactions(monthNumber);
                } else {
                    // Month has no transactions, show message
                    showNoTransactions(getMonthName(monthNumber));
                }
            }
        });
    });

    // Helper function to get month name
    function getMonthName(monthNumber) {
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months[monthNumber - 1];
    }
});