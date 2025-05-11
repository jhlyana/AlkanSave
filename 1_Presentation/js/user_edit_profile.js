// Wait until DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // 1. Form submission → show success modal
  const form = document.getElementById('editProfileForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      // Show success modal
      const successModal = document.getElementById('successModal');
      if (successModal) {
        successModal.style.display = 'flex';
        // Hide again after 2 seconds
        setTimeout(() => {
          successModal.style.display = 'none';
        }, 2000);
      }
      // You can also send form data here via AJAX, etc.
    });
  }

  // 2. Back button → navigate to profile page
  const backBtn = document.getElementById('back-btn');
  if (backBtn) {
    backBtn.addEventListener('click', function() {
      window.location.href = 'user_profile.html';
    });
  }

  // 3. Cancel button → reset form fields
  const cancelBtn = document.getElementById('cancel-btn');
  if (cancelBtn && form) {
    cancelBtn.addEventListener('click', function() {
      form.reset();
    });
  }

  // 4. Send Code & Confirm Code buttons → simulate action
  const sendCodeBtn = document.getElementById('send-code-btn');
  if (sendCodeBtn) {
    sendCodeBtn.addEventListener('click', function() {
      alert('Verification code sent!');
    });
  }
  const confirmCodeBtn = document.getElementById('confirm-code-btn');
  if (confirmCodeBtn) {
    confirmCodeBtn.addEventListener('click', function() {
      alert('Code confirmed!');
    });
  }

  // 5. Save Profile button → show success modal immediately
  const saveBtn = document.querySelector('.save-profile-btn');
  if (saveBtn) {
    saveBtn.addEventListener('click', function() {
      const successModal = document.getElementById('successModal');
      if (successModal) {
        successModal.style.display = 'flex';
      }
    });
  }

  // 6. Close button inside the success modal
  const closeModalBtn = document.getElementById('closeSuccessModal');
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', function() {
      const successModal = document.getElementById('successModal');
      if (successModal) {
        successModal.style.display = 'none';
      }
    });
  }
});
