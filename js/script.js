// Will be renamed to js/script.js and populated later
document.addEventListener('DOMContentLoaded', function () {
    console.log('Auth Page Loaded');

    const formTitle = document.getElementById('formTitle');
    const authForm = document.getElementById('authForm');
    const submitButton = document.getElementById('submitButton');
    const toggleToRegisterLink = document.getElementById('toggleToRegisterLink');
    const toggleToLoginLink = document.getElementById('toggleToLoginLink');
    const messageArea = document.getElementById('messageArea');

    let isLoginMode = true;

    function toggleMode() {
        isLoginMode = !isLoginMode;
        if (isLoginMode) {
            formTitle.textContent = 'Login';
            submitButton.textContent = 'Login';
            toggleToRegisterLink.classList.remove('hidden');
            toggleToLoginLink.classList.add('hidden');
            authForm.action = 'actions/login_action.php'; // Update action
        } else {
            formTitle.textContent = 'Register';
            submitButton.textContent = 'Register';
            toggleToRegisterLink.classList.add('hidden');
            toggleToLoginLink.classList.remove('hidden');
            authForm.action = 'actions/register_action.php'; // Update action
        }
        messageArea.textContent = ''; // Clear messages
        authForm.reset(); // Reset form fields
    }

    toggleToRegisterLink.addEventListener('click', function (e) {
        e.preventDefault();
        toggleMode();
    });

    toggleToLoginLink.addEventListener('click', function (e) {
        e.preventDefault();
        toggleMode();
    });

    // Placeholder for form submission handling
    authForm.addEventListener('submit', function(e) {
        e.preventDefault();
        messageArea.textContent = ''; // Clear previous messages

        const formData = new FormData(authForm);
        const actionUrl = isLoginMode ? 'actions/login_action.php' : 'actions/register_action.php';

        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageArea.textContent = data.message;
                messageArea.style.color = 'green';
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (!isLoginMode) {
                    // If registration was successful, switch to login mode
                    toggleMode();
                    messageArea.textContent = 'Registration successful! Please login.';
                    messageArea.style.color = 'green';
                }
            } else {
                messageArea.textContent = data.message || 'An error occurred.';
                messageArea.style.color = 'red';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageArea.textContent = 'An error occurred. Please try again.';
            messageArea.style.color = 'red';
        });
    });
});
