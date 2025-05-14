// Fixed version of savingsModal.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('SavingsModal script loaded');
    
    // Hide the modal on page load
    const modal = document.getElementById('addSmodal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Set up all modal listeners
    setupModalListeners();
});

function setupModalListeners() {
    console.log('Setting up modal listeners');
    
    // Setup the confirm add savings button
    const confirmAddSButton = document.getElementById('confirmAddS');
    if (confirmAddSButton) {
        confirmAddSButton.addEventListener('click', function() {
            const goalIdInput = document.getElementById('savingsGoalId');
            if (goalIdInput && goalIdInput.value) {
                addSavings(goalIdInput.value);
            } else {
                console.error('No goal ID set in hidden field');
                showMessage('Error: Missing goal information');
            }
        });
    }
    
    // Setup the cancel button
    const cancelAddSButton = document.getElementById('cancelAddS');
    if (cancelAddSButton) {
        cancelAddSButton.addEventListener('click', function() {
            closeAddSavingsModal();
        });
    }
}

function openAddSavingsModal(goalId) {
    console.log('Opening add savings modal for goal ID:', goalId);
    
    // Get modal elements
    const modal = document.getElementById('addSmodal');
    const goalIdInput = document.getElementById('savingsGoalId');
    const dateInput = document.getElementById('dateToday');
    
    // Set the goal ID in the hidden field
    if (goalIdInput) {
        goalIdInput.value = goalId;
        console.log('Set goal ID to:', goalId);
    } else {
        console.error('Goal ID input not found!');
    }
    
    // Set today's date
    if (dateInput) {
        const today = new Date();
        const year = today.getFullYear();
        let month = today.getMonth() + 1;
        let day = today.getDate();
        
        // Add leading zeros
        month = month < 10 ? '0' + month : month;
        day = day < 10 ? '0' + day : day;
        
        dateInput.value = `${year}-${month}-${day}`;
    }
    
    // Reset amount field
    const amountInput = document.getElementById('enterAmount');
    if (amountInput) {
        amountInput.value = '';
    }
    
    // Show the modal
    if (modal) {
        modal.style.display = 'flex'; // Use flex to center it
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
    } else {
        console.error('Add savings modal not found!');
    }
}

function closeAddSavingsModal() {
    const modal = document.getElementById('addSmodal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function addSavings(goalId) {
    console.log('Adding savings for goal ID:', goalId);
    
    // Get form values
    const amountInput = document.getElementById('enterAmount');
    const dateInput = document.getElementById('dateToday');
    
    if (!amountInput || !dateInput) {
        showMessage('Error: Form elements not found');
        return;
    }
    
    const amount = amountInput.value.trim();
    const dateSaved = dateInput.value;
    
    // Validate inputs
    if (!amount || isNaN(parseFloat(amount)) || parseFloat(amount) <= 0) {
        showMessage('Please enter a valid amount greater than 0');
        return;
    }
    
    if (!dateSaved) {
        showMessage('Please select a date');
        return;
    }
    
    // Disable button and show loading state
    const confirmBtn = document.getElementById('confirmAddS');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Processing...';
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('goalId', goalId);
    formData.append('amount', amount);
    formData.append('dateSaved', dateSaved);
    
    // Send AJAX request
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=addSavings', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        // Re-enable button
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Add Savings';
        }
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                // When successful, update the UI immediately instead of reloading
                updateUIAfterSavingsAdded(goalId, parseFloat(amount));
                
                // Show success message
                showMessage('Savings added successfully!', function() {
                    closeAddSavingsModal();
                    // No reload here - we'll update the UI directly
                });
            } else {
                showMessage(data.message || 'Failed to add savings');
            }
        } catch (e) {
            console.error('Error parsing response:', e);
            showMessage('Error processing server response');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while adding savings');
        
        // Re-enable button
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Add Savings';
        }
    });
}

// New function to update the UI without refreshing
function updateUIAfterSavingsAdded(goalId, amount) {
    console.log('Updating UI for goalId:', goalId, 'with amount:', amount);
    
    // Find the goal element
    const goalElement = document.querySelector(`[data-goal-id="${goalId}"]`);
    if (!goalElement) {
        console.error('Could not find goal element with ID:', goalId);
        return;
    }
    
    // Update the saved amount display
    const savedAmountElement = goalElement.querySelector('.amount-saved');
    if (savedAmountElement) {
        const currentText = savedAmountElement.textContent;
        const currentAmount = parseFloat(currentText.replace(/[^\d.-]/g, '')) || 0;
        const newAmount = currentAmount + amount;
        savedAmountElement.textContent = `P ${newAmount.toFixed(2)}`;
    }
    
    // Update left to save if it exists
    const leftToSaveElement = goalElement.querySelector('.left-to-save');
    if (leftToSaveElement) {
        const currentText = leftToSaveElement.textContent;
        const currentLeft = parseFloat(currentText.replace(/[^\d.-]/g, '')) || 0;
        const newLeft = Math.max(0, currentLeft - amount);
        leftToSaveElement.textContent = `P ${newLeft.toFixed(2)}`;
    }
    
    console.log('UI updated successfully');
}

// Helper function to format currency (if needed)
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}