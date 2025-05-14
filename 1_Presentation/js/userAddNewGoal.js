document.addEventListener('DOMContentLoaded', function() {
    // Load categories when page loads
    loadCategories();
    
    // Set current date as default
    const today = new Date().toISOString().split('T')[0];
    if (document.getElementById('dateToday')) {
        document.getElementById('dateToday').value = today;
    }
    
    // Set minimum date for target date (today)
    if (document.getElementById('targetDate')) {
        document.getElementById('targetDate').setAttribute('min', today);
    }
    
    // Event listeners
    if (document.getElementById('addCBtn')) {
        document.getElementById('addCBtn').addEventListener('click', openAddCategoryModal);
    }
    
    if (document.getElementById('confirmAddC')) {
        document.getElementById('confirmAddC').addEventListener('click', addCategory);
    }
    
    if (document.getElementById('cancelAddC')) {
        document.getElementById('cancelAddC').addEventListener('click', closeAddCategoryModal);
    }
    
    // Form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            addGoal();
        });
    }
});

function showMessage(message, callback) {
    const modal = document.getElementById('messageModal');
    if (!modal) {
        alert(message); // Fallback if modal doesn't exist
        if (callback) callback();
        return;
    }
    
    const messageText = document.getElementById('messageModalText');
    if (messageText) {
        messageText.textContent = message;
    }
    
    modal.style.display = 'flex';
    
    // Update confirm button behavior
    const confirmBtn = document.getElementById('confirmMessage');
    if (confirmBtn) {
        // Remove previous click handlers
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        
        document.getElementById('confirmMessage').onclick = function() {
            modal.style.display = 'none';
            if (callback) callback();
        };
    }
}

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

function populateCategoryDropdown(categories) {
    const dropdown = document.getElementById('categories');
    if (!dropdown) return;
    
    dropdown.innerHTML = '';
    
    // Add default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '-- Select Category --';
    defaultOption.disabled = true;
    defaultOption.selected = true;
    dropdown.appendChild(defaultOption);
    
    if (!categories || categories.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No categories available';
        option.disabled = true;
        dropdown.appendChild(option);
        return;
    }
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.CategoryID;
        option.textContent = category.CategoryName;
        dropdown.appendChild(option);
    });
}

function openAddCategoryModal(e) {
    if (e) e.preventDefault(); // Prevent any default behavior
    
    const modal = document.getElementById('addCmodal');
    if (modal) {
        modal.style.display = 'flex'; // Use flex for centering
    }
}

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
    
    // Create form data for the request
    const formData = new FormData();
    formData.append('action', 'addCategory');
    formData.append('categoryName', categoryName);
    
    // Send the request to the server
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
            loadCategories(); // Refresh categories
        } else {
            showMessage(data.message || 'Failed to add category');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while adding the category');
    });
}

function addGoal() {
    // Get form values
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
    
    // Validate inputs
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
    
    // Create form data for the request
    const formData = new FormData();
    formData.append('action', 'addGoal');
    formData.append('categoryId', categoryId);
    formData.append('goalName', goalName);
    formData.append('targetAmount', targetAmount);
    formData.append('startDate', startDate);
    formData.append('targetDate', targetDate);
    
    // Send the request to the server
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