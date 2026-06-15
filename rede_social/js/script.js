const btns = document.querySelectorAll(".nav-btn");

btns.forEach(btn => {
  btn.addEventListener("click", () => {
    btns.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
  });
});