const addCBtn = document.getElementById("addCBtn");
const addCModal = document.getElementById("addCmodal");
const confirmaddC = document.getElementById("confirmAddC");
const canceladdC = document.getElementById("cancelAddC");

if (addCBtn && addCModal) {
    addCBtn.addEventListener("click", (e) => {
      e.preventDefault();
      addCModal.style.display = "flex";
  });

  confirmaddC?.addEventListener("click", () => {
      window.location.href = "user_savings.html"; 
  });

  canceladdC?.addEventListener("click", () => {
      window.location.href = "user_add_goal.html";
  });
}