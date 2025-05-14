// Wait for the page to fully load before executing scripts
document.addEventListener('DOMContentLoaded', function() {
    // Load available categories from server
    loadCategories();
    
    // Set today's date as default for the start date field
    const today = new Date().toISOString().split('T')[0];
    if (document.getElementById('dateToday')) {
        document.getElementById('dateToday').value = today;
    }
    
    // Prevent selecting past dates for target date
    if (document.getElementById('targetDate')) {
        document.getElementById('targetDate').setAttribute('min', today);
    }
    
    // Set up event listeners for category management
    if (document.getElementById('addCBtn')) {
        document.getElementById('addCBtn').addEventListener('click', openAddCategoryModal);
    }
    
    if (document.getElementById('confirmAddC')) {
        document.getElementById('confirmAddC').addEventListener('click', addCategory);
    }
    
    if (document.getElementById('cancelAddC')) {
        document.getElementById('cancelAddC').addEventListener('click', closeAddCategoryModal);
    }
    
    // Handle form submission for adding new goal
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            addGoal();
        });
    }
});

// Display a message to user in modal or fallback to alert
function showMessage(message, callback) {
    const modal = document.getElementById('messageModal');
    if (!modal) {
        alert(message);
        if (callback) callback();
        return;
    }
    
    const messageText = document.getElementById('messageModalText');
    if (messageText) {
        messageText.textContent = message;
    }
    
    modal.style.display = 'flex';
    
    // Update confirm button to prevent multiple handlers
    const confirmBtn = document.getElementById('confirmMessage');
    if (confirmBtn) {
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        
        document.getElementById('confirmMessage').onclick = function() {
            modal.style.display = 'none';
            if (callback) callback();
        };
    }
}

// Fetch categories from server and populate dropdown
function loadCategories() {
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=getGoals')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateCategoryDropdown(data.categories);
            } else {
                console.error('Failed to load categories');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Fill category dropdown with options from server
function populateCategoryDropdown(categories) {
    const dropdown = document.getElementById('categories');
    if (!dropdown) return;
    
    dropdown.innerHTML = '';
    
    // Create default disabled option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '-- Select Category --';
    defaultOption.disabled = true;
    defaultOption.selected = true;
    dropdown.appendChild(defaultOption);
    
    // Handle case when no categories exist
    if (!categories || categories.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No categories available';
        option.disabled = true;
        dropdown.appendChild(option);
        return;
    }
    
    // Add each category as an option
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.CategoryID;
        option.textContent = category.CategoryName;
        dropdown.appendChild(option);
    });
}

// Show modal for adding new category
function openAddCategoryModal(e) {
    if (e) e.preventDefault();
    
    const modal = document.getElementById('addCmodal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// Hide category modal and clear input
function closeAddCategoryModal() {
    const modal = document.getElementById('addCmodal');
    const input = document.getElementById('categoryName');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (input) {
        input.value = '';
    }
}

// Send new category to server and update UI
function addCategory() {
    const categoryNameInput = document.getElementById('categoryName');
    if (!categoryNameInput) {
        showMessage('Category input not found');
        return;
    }
    
    const categoryName = categoryNameInput.value.trim();
    
    if (!categoryName) {
        showMessage('Please enter a category name');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'addCategory');
    formData.append('categoryName', categoryName);
    
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=addCategory', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage('Category added successfully!');
            closeAddCategoryModal();
            loadCategories();
        } else {
            showMessage(data.message || 'Failed to add category');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while adding the category');
    });
}

// Validate and submit new goal data to server
function addGoal() {
    const categoryIdInput = document.getElementById('categories');
    const goalNameInput = document.getElementById('goalName') || document.getElementById('lastName');
    const targetAmountInput = document.getElementById('targetAmount') || document.getElementById('username');
    const startDateInput = document.getElementById('dateToday');
    const targetDateInput = document.getElementById('targetDate');
    
    if (!categoryIdInput || !goalNameInput || !targetAmountInput || !startDateInput || !targetDateInput) {
        showMessage('Form inputs not found');
        return;
    }
    
    const categoryId = categoryIdInput.value;
    const goalName = goalNameInput.value.trim();
    const targetAmount = targetAmountInput.value;
    const startDate = startDateInput.value;
    const targetDate = targetDateInput.value;
    
    // Validate form inputs
    if (!categoryId || categoryId === '') {
        showMessage('Please select a category');
        return;
    }
    
    if (!goalName) {
        showMessage('Please enter a goal name');
        return;
    }
    
    if (!targetAmount || isNaN(parseFloat(targetAmount)) || parseFloat(targetAmount) <= 0) {
        showMessage('Please enter a valid target amount');
        return;
    }
    
    if (!startDate || !targetDate) {
        showMessage('Please select both dates');
        return;
    }
    
    if (new Date(targetDate) < new Date(startDate)) {
        showMessage('Target date cannot be before start date');
        return;
    }
    
    // Prepare and send goal data to server
    const formData = new FormData();
    formData.append('action', 'addGoal');
    formData.append('categoryId', categoryId);
    formData.append('goalName', goalName);
    formData.append('targetAmount', targetAmount);
    formData.append('startDate', startDate);
    formData.append('targetDate', targetDate);
    
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=addGoal', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage('Goal added successfully!', function() {
                window.location.href = 'user_savings.html';
            });
        } else {
            showMessage(data.message || 'Failed to add goal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while adding the goal');
    });
}