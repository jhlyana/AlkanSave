// messageModal.js - Simplified version
function showMessage(message) {
    const modal = document.getElementById('messageModal');
    const messageText = document.getElementById('messageModalText');
    
    if (!modal || !messageText) {
        alert(message);
        return;
    }
    
    messageText.textContent = message;
    modal.style.display = 'flex';
    
    // Update the confirm button to clone it and remove previous handlers
    const confirmBtn = document.getElementById('confirmMessage');
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    document.getElementById('confirmMessage').onclick = function() {
        modal.style.display = 'none';
    };
    
    modal.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}

window.showMessage = showMessage;