document.addEventListener('DOMContentLoaded', function () {
    console.log('Auth Page Loaded'); // As per user's latest full code block

    const formTitle = document.getElementById('formTitle');
    const authForm = document.getElementById('authForm');
    const submitButton = document.getElementById('submitButton');
    const toggleToRegisterLink = document.getElementById('toggleToRegisterLink');
    const toggleToLoginLink = document.getElementById('toggleToLoginLink');
    const messageArea = document.getElementById('messageArea');
    const registrationFields = document.getElementById('registrationFields');

    let isLoginMode = true; // Initial state is login mode

    /**
     * Displays a message in the messageArea with appropriate styling.
     * @param {string} message - The message to display.
     * @param {'success' | 'error' | 'info'} type - The type of message to determine styling.
     */
    function displayMessage(message, type = 'info') {
        messageArea.textContent = message;
        let className = 'mb-4 text-sm text-center';
        if (type === 'success') {
            className += ' text-green-600';
        } else if (type === 'error') {
            className += ' text-red-600';
        } else { // info or default
            className += ' text-gray-700';
        }
        messageArea.className = className;
    }

    function toggleMode() {
        isLoginMode = !isLoginMode;

        if (isLoginMode) {
            formTitle.textContent = 'Login';
            submitButton.textContent = 'Login';
            toggleToRegisterLink.classList.remove('hidden');
            toggleToLoginLink.classList.add('hidden');
            if (registrationFields) {
                registrationFields.classList.add('hidden');
                // Remove required attribute from all inputs within registration fields
                registrationFields.querySelectorAll('input, select').forEach(input => input.removeAttribute('required'));
            }
            authForm.action = 'actions/login_action.php';
        } else {
            formTitle.textContent = 'Register';
            submitButton.textContent = 'Register';
            toggleToRegisterLink.classList.add('hidden');
            toggleToLoginLink.classList.remove('hidden');
            if (registrationFields) {
                registrationFields.classList.remove('hidden');
                // Add required attribute to necessary inputs within registration fields
                const firstNameField = document.getElementById('first_name'); // Get by ID
                const lastNameField = document.getElementById('last_name');   // Get by ID
                if (firstNameField) firstNameField.setAttribute('required', 'required');
                if (lastNameField) lastNameField.setAttribute('required', 'required');
            }
            authForm.action = 'actions/register_action.php';
        }

        displayMessage('');
        authForm.reset();
    }


    toggleToRegisterLink.addEventListener('click', function (e) {
        e.preventDefault();
        toggleMode();
    });

    toggleToLoginLink.addEventListener('click', function (e) {
        e.preventDefault();
        toggleMode();
    });

    authForm.addEventListener('submit', function (e) {
        e.preventDefault();
        displayMessage('');

        const allInputs = authForm.querySelectorAll('input, select');
        if (isLoginMode && registrationFields) { // Only do this if in login mode
            registrationFields.querySelectorAll('input, select').forEach(input => {
                 if (input.id === 'first_name' || input.id === 'last_name') {
                    input.removeAttribute('required');
                }
            });
        }


        const formData = new FormData(authForm);
        const actionUrl = authForm.action;

        displayMessage('Processing...', 'info');
        submitButton.disabled = true;

        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().catch(() => {
                        throw new Error(`HTTP error! status: ${response.status} - Could not parse error response from ${actionUrl}.`);
                    }).then(errData => {
                        throw new Error(errData.message || `HTTP error! status: ${response.status} from ${actionUrl}.`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayMessage(data.message || 'Operation successful!', 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 500);
                    } else if (!isLoginMode && actionUrl.includes('register_action.php')) {
                        toggleMode();
                        displayMessage(data.message || 'Registration successful! Please login.', 'success');
                    }
                } else {
                    displayMessage(data.message || 'An error occurred.', 'error');
                    if (data.debug || data.debug_info || data.debug_info_on_error || data.FORCED_DEBUG_OUTPUT) {
                        console.warn('Server Debug Info:', data.debug || data.debug_info || data.debug_info_on_error || data.FORCED_DEBUG_OUTPUT);
                    }
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                displayMessage(error.message || 'A network error occurred. Please try again.', 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
            });
    });

    // Initialize to Login mode
    isLoginMode = false;
    toggleMode();


});

// Placeholder logout handler from user's provided code - will not be active with our PHP logout
const logoutForm = document.querySelector('form[action="logout.php"]');
if (logoutForm) {
    logoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await new Promise(resolve => setTimeout(resolve, 500));
        window.location.href = window.location.pathname;
    });
}
