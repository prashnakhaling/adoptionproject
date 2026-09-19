// for log in 
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('authModal');
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');

  document.getElementById('loginLink').addEventListener('click', function (e) {
    e.preventDefault();
    modal.style.display = 'block';
    loginForm.style.display = 'block';
    signupForm.style.display = 'none';
  });

  document.getElementById('showSignup').addEventListener('click', function (e) {
    e.preventDefault();
    loginForm.style.display = 'none';
    signupForm.style.display = 'block';
  });

  document.getElementById('showLogin').addEventListener('click', function (e) {
    e.preventDefault();
    signupForm.style.display = 'none';
    loginForm.style.display = 'block';
  });

  // Close modal if clicking outside the modal content
  window.addEventListener('click', function (e) {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  });
});

//display registration message 
// function showLoginForm() {
//   document.getElementById('loginForm').style.display = 'block';
// }
