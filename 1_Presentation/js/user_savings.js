document.addEventListener('DOMContentLoaded', function() {
    checkSessionAndLoadGoals();
   
    // Event listeners
    document.getElementById('addGoalBtn').addEventListener('click', redirectToAddGoal);
    document.getElementById('searchBtn').addEventListener('click', searchGoals);
    document.getElementById('searchInput').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') searchGoals();
    });
});

function showMessage(message) {
    const modal = document.getElementById('messageModal');
    const messageText = document.getElementById('messageModalText');
    messageText.textContent = message;
    modal.style.display = 'flex';
   
    document.getElementById('confirmMessage').onclick = function() {
        modal.style.display = 'none';
    };
}

function checkSessionAndLoadGoals() {
    fetch('/AlkanSave/2_Application/controllers/AuthController.php?action=checkSession')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                loadGoals();
            } else {
                window.location.href = '/AlkanSave/1_Presentation/login.html';
            }
        })
        .catch(error => {
            console.error('Session check failed:', error);
            window.location.href = '/AlkanSave/1_Presentation/login.html';
        });
}

function loadGoals() {
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=getGoals')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderGoals(data.goals);
                populateCategoryDropdown(data.categories);
            } else {
                showNoGoalsMessage();
            }
        })
        .catch(error => {
            console.error('Error loading goals:', error);
            showNoGoalsMessage();
        });
}

function renderGoals(goals) {
    const goalsContainer = document.querySelector('.title-content');
    goalsContainer.innerHTML = '';
   
    if (!goals || goals.length === 0) {
        showNoGoalsMessage();
        return;
    }
   
    goals.forEach(goal => {
        const amountLeft = Math.max(goal.TargetAmount - goal.SavedAmount, 0);
        const progressPercentage = Math.min((goal.SavedAmount / goal.TargetAmount) * 100, 100);
       
        const goalCard = document.createElement('div');
        goalCard.className = 'goals-deadline';
        goalCard.dataset.goalId = goal.GoalID;
       
        goalCard.innerHTML = `
            <div class="category-goal-wrapper">
                <h5 class="category">${escapeHtml(goal.CategoryName)}: <span class="goal">${escapeHtml(goal.GoalName)}</span></h5>
                <button class="delete-goal-btn" title="Delete Goal" onclick="deleteGoal(${goal.GoalID})">
                    <img src="images/deleteIcon.png"/>
                </button>
            </div>
            <div class="goal-entry">
                <p>Amount Saved:</p>
                <h2>₱ ${formatCurrency(goal.SavedAmount)}</h2>
            </div>
            <div class="progress-container">
                <div class="progress-bar" style="width: ${progressPercentage}%"></div>
            </div>
            <h6 class="amountLeft">Left to save: <span class="amount">₱ ${formatCurrency(amountLeft)}</span></h6>
            <h6 class="amountLeft">Target Date: <span class="amount">${formatDate(goal.TargetDate)}</span></h6>
            <div class="category-goal-wrapper">
                <button class="edit-goal-btn" title="Edit Goal" onclick="openEditGoalModal(${goal.GoalID}, '${escapeHtml(goal.CategoryName)}', '${escapeHtml(goal.GoalName)}', ${goal.TargetAmount}, '${goal.TargetDate}')">
                    <img src="images/editIcon.png"/>Edit Goal
                </button>            
                <button class="edit-goal-btn" title="Add Savings" onclick="openAddSavingsModal(${goal.GoalID})">
                    <img src="images/addIcon.png"/>Add Savings
                </button>
            </div>
        `;
       
        goalsContainer.appendChild(goalCard);
    });
}

function showNoGoalsMessage() {
    const goalsContainer = document.querySelector('.title-content');
    goalsContainer.innerHTML = `
        <div class="no-goals-message">
            <img src="images/savings_icon.svg" alt="No Goals" class="empty-icon">
            <h3>No Savings Goals Yet</h3>
            <p>You haven't created any savings goals. Click "Add New Goal" to get started!</p>
        </div>
    `;
}

function populateCategoryDropdown(categories) {
    const dropdown = document.getElementById('categories');
    dropdown.innerHTML = '';
   
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.CategoryID;
        option.textContent = category.CategoryName;
        dropdown.appendChild(option);
    });
}

function redirectToAddGoal() {
    window.location.href = 'user_add_goal.html';
}

function searchGoals() {
    const searchTerm = document.getElementById('searchInput').value.trim();
   
    fetch(`/AlkanSave/2_Application/controllers/SavingsController.php?action=searchGoals&term=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderGoals(data.goals);
            } else {
                console.error('Search failed');
            }
        })
        .catch(error => console.error('Error:', error));
}

function openAddSavingsModal(goalId) {
    const modal = document.getElementById('addSmodal');
    
    // Set the hidden goal ID field
    const hiddenField = document.getElementById('savingsGoalId');
    if (hiddenField) {
        hiddenField.value = goalId;
    }
    
    // Set today's date
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateToday').value = today;
    
    // Clear any previous amount
    const amountInput = document.getElementById('enterAmount');
    if (amountInput) {
        amountInput.value = '';
    }
    
    // Add the 'show' class to make it visible and centered
    modal.classList.add('show');
    
    // Set display to flex for proper centering
    modal.style.display = 'flex';
    
    document.getElementById('confirmAddS').onclick = function() {
        addSavings(goalId);
    };
    
    document.getElementById('cancelAddS').onclick = function() {
        closeAddSavingsModal();
    };
}

function closeAddSavingsModal() {
    const modal = document.getElementById('addSmodal');
    modal.classList.remove('show');
    modal.style.display = 'none';
}

function addSavings(goalId) {
    console.log("Starting addSavings for goal:", goalId);
    
    const amountInput = document.getElementById('enterAmount');
    const dateInput = document.getElementById('dateToday');
    
    const amount = parseFloat(amountInput.value);
    const date = dateInput.value;

    // Clear previous errors
    amountInput.classList.remove('error');
    dateInput.classList.remove('error');

    // Validate inputs
    let isValid = true;
    
    if (isNaN(amount) || amount <= 0) {
        amountInput.classList.add('error');
        showMessage('Please enter a valid amount greater than 0');
        isValid = false;
    }
    
    if (!date) {
        dateInput.classList.add('error');
        showMessage('Please select a date');
        isValid = false;
    }
    
    if (!isValid) return;

    // Show loading state
    const confirmBtn = document.getElementById('confirmAddS');
    const originalText = confirmBtn.textContent;
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Processing...';
    
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=addSavings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            goalId: goalId,
            amount: amount,
            dateSaved: date
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { 
                throw new Error(err.message || 'Server error'); 
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message || 'Savings added successfully!');
            loadGoals();
            closeAddSavingsModal();
        } else {
            throw new Error(data.message || 'Failed to add savings');
        }
    })
    .catch(error => {
        console.error("Error in addSavings:", error);
        showMessage(error.message);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalText;
    });
}

function openEditGoalModal(goalId, currentCategory, goalName, targetAmount, targetDate) {
    const modal = document.getElementById('editGoalmodal');
    modal.classList.add('show');
   
    document.getElementById('goalName').value = goalName;
    document.getElementById('targetAmount').value = targetAmount;
    document.getElementById('targetDate').value = targetDate;
   
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateToday').value = today;
   
    modal.dataset.goalId = goalId;
   
    document.getElementById('confirmeditGoal').onclick = function() {
        updateGoal(goalId);
    };
    document.getElementById('canceleditGoal').onclick = function() {
        modal.classList.remove('show');
    };
}

function closeEditModal() {
    document.getElementById('editGoalmodal').classList.remove('show');
}

function updateGoal(goalId) {
    const categorySelect = document.querySelector('#editGoalmodal select');
    const categoryId = categorySelect.value;
    const goalName = document.getElementById('goalName').value;
    const targetAmount = parseFloat(document.getElementById('targetAmount').value);
    const targetDate = document.getElementById('targetDate').value;
   
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=editGoal', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            goalId: goalId,
            categoryId: categoryId,
            goalName: goalName,
            targetAmount: targetAmount,
            targetDate: targetDate
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadGoals();
            closeEditModal();
        } else {
            showMessage('Failed to update goal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while updating the goal');
    });
}

function deleteGoal(goalId) {
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.style.display = 'flex';
    deleteModal.dataset.goalId = goalId;

    document.getElementById('confirmDelete').onclick = function() {
        performDeleteGoal(goalId);
        deleteModal.style.display = 'none';
    };
   
    document.getElementById('cancelDelete').onclick = function() {
        deleteModal.style.display = 'none';
    };
}

function performDeleteGoal(goalId) {
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=deleteGoal', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            goalId: goalId
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadGoals();
        } else {
            showMessage('Failed to delete goal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while deleting the goal');
    });
}

// Helper functions
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).replace(/\//g, '-');
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}