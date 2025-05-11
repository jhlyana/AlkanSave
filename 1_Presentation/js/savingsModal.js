//Edit Modal
const editGoalBtn = document.getElementById("editGoalBtn");
const editGoalModal = document.getElementById("editGoalmodal");
const confirmeditGoal = document.getElementById("confirmeditGoal");
const canceleditGoal = document.getElementById("canceleditGoal");

if (editGoalBtn && editGoalModal) {
    editGoalBtn.addEventListener("click", (e) => {
      e.preventDefault();
      editGoalModal.style.display = "flex";
  });

  confirmeditGoal?.addEventListener("click", () => {
      window.location.href = "user_savings.html"; 
  });

  canceleditGoal?.addEventListener("click", () => {
      window.location.href = "user_savings.html";
  });
}

//Add Savings
const addSBtn = document.getElementById("addSavingsBtn");
const addSModal = document.getElementById("addSmodal");
const confirmaddS = document.getElementById("confirmAddS");
const canceladdS = document.getElementById("cancelAddS");

if (addSBtn && addSModal) {
    addSBtn.addEventListener("click", (e) => {
      e.preventDefault();
      addSModal.style.display = "flex";
  });

  confirmaddS?.addEventListener("click", () => {
      window.location.href = "user_savings.html"; 
  });

  canceladdS?.addEventListener("click", () => {
      window.location.href = "user_savings.html";
  });
}

const deleteBtn = document.getElementById("deleteButton");
const deletetModal = document.getElementById("deleteModal");
const confirmdelete = document.getElementById("confirmDelete");
const canceldelete = document.getElementById("cancelDelete");

if (deleteBtn && deleteModal) {
    deleteBtn.addEventListener("click", (e) => {
      e.preventDefault();
      deleteModal.style.display = "flex";
  });

  confirmdelete?.addEventListener("click", () => {
      window.location.href = "user_savings.html"; 
  });

  canceldelete?.addEventListener("click", () => {
      window.location.href = "user_savings.html"; 
  });
}