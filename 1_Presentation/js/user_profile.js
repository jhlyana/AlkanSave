document.addEventListener('DOMContentLoaded', async () => {
    await loadProfileData();
});

async function loadProfileData() {
    try {
        const response = await fetch('../2_Application/controllers/ProfileController.php?action=getProfile');
        const result = await response.json();
        
        if (result.success) {
            updateProfileDisplay(result.data);
        } else {
            showError('Failed to load profile data');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showError('Connection failed');
    }
}

function updateProfileDisplay(data) {
    document.getElementById('firstName').textContent = data.firstName || 'N/A';
    document.getElementById('lastName').textContent = data.lastName || 'N/A';
    document.getElementById('dateOfBirth').textContent = data.dateOfBirth || 'N/A';
    document.getElementById('username').textContent = data.username || 'N/A';
    document.getElementById('emailAddress').textContent = data.email || 'N/A';
    
    if (data.profilePicture) {
        document.getElementById('profilePreview').src = data.profilePicture;
    }
}

function showError(message) {
    document.getElementById('firstName').textContent = 'Error';
    document.getElementById('lastName').textContent = 'Error';
    document.getElementById('dateOfBirth').textContent = 'Error';
    document.getElementById('username').textContent = 'Error';
    document.getElementById('emailAddress').textContent = 'Error';
}