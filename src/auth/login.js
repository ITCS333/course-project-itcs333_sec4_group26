/*
  Client-side validation for the login form.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="login.js" defer></script>
  2. Make sure your HTML has the following elements with these IDs:
     - A form with id="login-form"
     - An email input with id="email"
     - A password input with id="password"
     - A div with id="message-container" to display messages.
     This div will be used to display success or error messages.
     Example: <div id="message-container"></div>
*/

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

const loginForm = document.getElementById('login-form');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const messageContainer = document.getElementById('message-container');

// --- Functions ---

/**
 * Display a message to the user in the message container.
 * 
 * @param {string} message - The message text to display.
 * @param {string} type - The type of message: 'success' or 'error'.
 */
function displayMessage(message, type) {
  messageContainer.textContent = message;
  messageContainer.className = type;
}

/**
 * Validate the email format using a regular expression.
 * 
 * @param {string} email - The email address to validate.
 * @returns {boolean} - True if the email is valid, false otherwise.
 */
function isValidEmail(email) {
  const emailRegex = /\S+@\S+\.\S+/;
  return emailRegex.test(email);
}

/**
 * Check if the password meets the minimum length requirement.
 * 
 * @param {string} password - The password to validate.
 * @returns {boolean} - True if the password is valid, false otherwise.
 */
function isValidPassword(password) {
  return password.length >= 8;
}

/**
 * Handle the login form submission.
 * 
 * @param {Event} event - The form submit event.
 */
function handleLogin(event) {
  event.preventDefault();
  
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();
  
  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }
  
  if (!isValidPassword(password)) {
    displayMessage("Password must be at least 8 characters.", "error");
    return;
  }
  
  displayMessage("Login successful!", "success");
  emailInput.value = '';
  passwordInput.value = '';
}

/**
 * Attach event listener to the login form.
 */
function setupLoginForm() {
  if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
  }
}

// --- Initial Page Load ---
// Call the main setup function to attach the event listener.
setupLoginForm();
