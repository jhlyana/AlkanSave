// Wait for page to fully load before executing
document.addEventListener('DOMContentLoaded', function() {
    // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
    console.log('SavingsModal script loaded');
    // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    
    // Hide the savings modal by default when page loads
    const modal = document.getElementById('addSmodal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Initialize all modal event listeners
    setupModalListeners();
});

// Set up all event listeners for the savings modal
function setupModalListeners() {
    // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
    console.log('Setting up modal listeners');
    // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    
    // Handle confirm button click
    const confirmAddSButton = document.getElementById('confirmAddS');
    if (confirmAddSButton) {
        confirmAddSButton.addEventListener('click', function() {
            const goalIdInput = document.getElementById('savingsGoalId');
            if (goalIdInput && goalIdInput.value) {
                addSavings(goalIdInput.value);
            } else {
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                console.error('No goal ID set in hidden field');
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                showMessage('Error: Missing goal information');
            }
        });
    }
    
    // Handle cancel button click
    const cancelAddSButton = document.getElementById('cancelAddS');
    if (cancelAddSButton) {
        cancelAddSButton.addEventListener('click', function() {
            closeAddSavingsModal();
        });
    }
}

// Open the add savings modal and prepare form
function openAddSavingsModal(goalId) {
    // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
    console.log('Opening add savings modal for goal ID:', goalId);
    // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    
    const modal = document.getElementById('addSmodal');
    const goalIdInput = document.getElementById('savingsGoalId');
    const dateInput = document.getElementById('dateToday');
    
    // Store the goal ID in hidden field for form submission
    if (goalIdInput) {
        goalIdInput.value = goalId;
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.log('Set goal ID to:', goalId);
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    } else {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.error('Goal ID input not found!');
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    }
    
    // Set default date to today
    if (dateInput) {
        const today = new Date();
        const year = today.getFullYear();
        let month = today.getMonth() + 1;
        let day = today.getDate();
        
        // Format month and day with leading zeros
        month = month < 10 ? '0' + month : month;
        day = day < 10 ? '0' + day : day;
        
        dateInput.value = `${year}-${month}-${day}`;
    }
    
    // Clear previous amount input
    const amountInput = document.getElementById('enterAmount');
    if (amountInput) {
        amountInput.value = '';
    }
    
    // Display the modal with flex centering
    if (modal) {
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
    } else {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.error('Add savings modal not found!');
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    }
}

// Close the add savings modal
function closeAddSavingsModal() {
    const modal = document.getElementById('addSmodal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Handle savings submission to server
function addSavings(goalId) {
    // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
    console.log('Adding savings for goal ID:', goalId);
    // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    
    const amountInput = document.getElementById('enterAmount');
    const dateInput = document.getElementById('dateToday');
    
    if (!amountInput || !dateInput) {
        showMessage('Error: Form elements not found');
        return;
    }
    
    const amount = amountInput.value.trim();
    const dateSaved = dateInput.value;
    
    // Validate the amount is positive number
    if (!amount || isNaN(parseFloat(amount)) || parseFloat(amount) <= 0) {
        showMessage('Please enter a valid amount greater than 0');
        return;
    }
    
    // Validate date is selected
    if (!dateSaved) {
        showMessage('Please select a date');
        return;
    }
    
    // Disable button during processing
    const confirmBtn = document.getElementById('confirmAddS');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Processing...';
    }
    
    // Prepare form data for submission
    const formData = new FormData();
    formData.append('goalId', goalId);
    formData.append('amount', amount);
    formData.append('dateSaved', dateSaved);
    
    // Send data to server
    fetch('/AlkanSave/2_Application/controllers/SavingsController.php?action=addSavings', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.log('Response status:', response.status);
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        return response.text();
    })
    .then(text => {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.log('Raw response:', text);
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        // Re-enable button after response
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Add Savings';
        }
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                // Update UI without page reload
                updateUIAfterSavingsAdded(goalId, parseFloat(amount));
                
                showMessage('Savings added successfully!', function() {
                    closeAddSavingsModal();
                });
            } else {
                showMessage(data.message || 'Failed to add savings');
            }
        } catch (e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            console.error('Error parsing response:', e);
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            showMessage('Error processing server response');
        }
    })
    .catch(error => {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.error('Error:', error);
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        showMessage('An error occurred while adding savings');
        
        // Re-enable button on error
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Add Savings';
        }
    });
}

// Update the UI after successful savings addition
function updateUIAfterSavingsAdded(goalId, amount) {
    // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
    console.log('Updating UI for goalId:', goalId, 'with amount:', amount);
    // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
    
    // Find the goal card in the DOM
    const goalElement = document.querySelector(`[data-goal-id="${goalId}"]`);
    if (!goalElement) {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        console.error('Could not find goal element with ID:', goalId);
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        return;
    }
    
    // Update saved amount display
    const savedAmountElement = goalElement.querySelector('.amount-saved');
    if (savedAmountElement) {
        const currentText = savedAmountElement.textContent;
        const currentAmount = parseFloat(currentText.replace(/[^\d.-]/g, '')) || 0;
        const newAmount = currentAmount + amount;
        savedAmountElement.textContent = `P ${newAmount.toFixed(2)}`;
    }
    
    // Update remaining amount display if it exists
    const leftToSaveElement = goalElement.querySelector('.left-to-save');
    if (leftToSaveElement) {
        const currentText = leftToSaveElement.textContent;
        const currentLeft = parseFloat(currentText.replace(/[^\d.-]/g, '')) || 0;
        const newLeft = Math.max(0, currentLeft - amount);
        leftToSaveElement.textContent = `P ${newLeft.toFixed(2)}`;
    }
    
    // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
    console.log('UI updated successfully');
    // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
}

// Helper function to format currency values (unused in current implementation)
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}