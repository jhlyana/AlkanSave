document.addEventListener('DOMContentLoaded', function() {
    loadUserData();
    setupEventListeners();
});

let emailVerified = false;
let verificationCode = '';

async function loadUserData() {
    try {
        const response = await fetch('../2_Application/controllers/ProfileController.php?action=getProfile');
        const result = await response.json();
        
        if (result.success) {
            populateForm(result.data);
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

function populateForm(data) {
    document.getElementById('firstName').value = data.firstName || '';
    document.getElementById('lastName').value = data.lastName || '';
    document.getElementById('email').value = data.email || '';
    
    if (data.profilePicture) {
        document.getElementById('profilePreview').src = data.profilePicture;
    }
}

function setupEventListeners() {
    // Profile picture upload
    document.getElementById('profilePicture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Send verification code
    document.getElementById('sendCodeBtn').addEventListener('click', async function() {
        const email = document.getElementById('email').value;
        if (!email) {
            alert('Please enter an email address first');
            return;
        }

        try {
            const response = await fetch('../2_Application/controllers/ProfileController.php?action=sendVerificationCode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });
            
            const result = await response.json();
            if (result.success) {
                verificationCode = result.code;
                alert('Verification code sent! (For demo: ' + result.code + ')');
            } else {
                alert('Failed to send verification code');
            }
        } catch (error) {
            alert('Error sending verification code');
        }
    });

    // Confirm verification code
    document.getElementById('confirmCodeBtn').addEventListener('click', function() {
        const enteredCode = document.getElementById('verificationCode').value;
        if (enteredCode === verificationCode) {
            emailVerified = true;
            alert('Email verified successfully!');
        } else {
            alert('Invalid verification code');
        }
    });

    // Form submission
    document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('firstName', document.getElementById('firstName').value);
        formData.append('lastName', document.getElementById('lastName').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('confirmPassword', document.getElementById('confirmPassword').value);
        formData.append('emailVerified', emailVerified);
        
        const profilePicture = document.getElementById('profilePicture').files[0];
        if (profilePicture) {
            formData.append('profilePicture', profilePicture);
        }

        try {
            const response = await fetch('../2_Application/controllers/ProfileController.php?action=updateProfile', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                showSuccessModal();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error updating profile');
        }
    });

    // Success modal close
    document.getElementById('closeSuccessModal').addEventListener('click', function() {
        document.getElementById('successModal').style.display = 'none';
        window.location.href = 'user_profile.html';
    });
}

function showSuccessModal() {
    document.getElementById('successModal').style.display = 'flex';
}