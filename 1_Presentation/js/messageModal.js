// Displays a message in a modal dialog box
function showMessage(message) {
    // Get the modal and text elements from the page
    const modal = document.getElementById('messageModal');
    const messageText = document.getElementById('messageModalText');
    
    // Fallback to alert if modal elements don't exist
    if (!modal || !messageText) {
        alert(message);
        return;
    }
    
    // Set the message content and show the modal
    messageText.textContent = message;
    modal.style.display = 'flex';
    
    // Replace confirm button to clear any existing click handlers
    const confirmBtn = document.getElementById('confirmMessage');
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    
    // Set click handler for the new confirm button
    document.getElementById('confirmMessage').onclick = function() {
        modal.style.display = 'none';
    };
    
    // Close modal when clicking outside the content
    modal.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}

// Make the function available globally
window.showMessage = showMessage;