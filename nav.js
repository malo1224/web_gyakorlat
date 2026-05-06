const logoutBtn = document.getElementById("logoutBtn");
const logintBtn = document.getElementById("logintBtn");
const registerBtn = document.getElementById("registerBtn");
const userSpan = document.getElementById("userName");

logoutBtn.addEventListener("click", () => {
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("email");
    window.location.href = "login.html";
});

// logged in
if (sessionStorage.getItem("token")) {
    logoutBtn.style.display = "block";
    logintBtn.style.display = "none";
    registerBtn.style.display = "none";
    userSpan.innerText = sessionStorage.getItem("email");
}
else {
    logoutBtn.style.display = "none";
    logintBtn.style.display = "block";
    registerBtn.style.display = "block";
}