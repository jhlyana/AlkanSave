document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("viewUser");
  const userDetails = document.getElementById("userDetails");
  const cancelModal = document.getElementById("cancelViewUserModal");
  const userMgmtLink = document.getElementById("userManagementLink");

  userDetails.addEventListener("click", function() {
    modal.classList.remove("hide");
    modal.classList.add("show");
  });

  cancelModal.addEventListener("click", function() {
    modal.classList.remove("show");
    modal.classList.add("hide");
  });

  userMgmtLink.addEventListener("click", function(e) {
    if (window.location.pathname.includes("admin_userM.html")) {
      e.preventDefault();
      modal.classList.remove("show");
      modal.classList.add("hide");
    }
  });
});

function redirectToPage() {
  window.location.href = 'admin_userMDAcc.html';
}

function goBack() {
  window.location.href = 'admin_userM.html';
}

const userAccount = document.getElementById("userAccount");
const accountModal = document.getElementById("accountModal");
const activateAccount = document.getElementById("activateAccount");
const stayDeactivated = document.getElementById("stayDeactivated");

if (userAccount && accountModal) {
  userAccount.addEventListener("click", (e) => {
      e.preventDefault();
      accountModal.style.display = "flex";
  });

  activateAccount?.addEventListener("click", () => {
      window.location.href = "admin_userM.html"; 
  });

  stayDeactivated?.addEventListener("click", () => {
      window.location.href = "admin_userMDAcc.html";
  });
}