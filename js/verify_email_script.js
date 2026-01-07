document.addEventListener('DOMContentLoaded', function () {
    console.log('Verify Email Page Loaded'); // User's console log

    const verifyEmailForm = document.getElementById('verifyEmailForm');
    const emailInput = document.getElementById('email'); // Expected input field for email
    const verificationCodeInput = document.getElementById('verification_code');
    const verifyButton = document.getElementById('verifyButton'); // Expected button ID
    const resendCodeLink = document.getElementById('resendCodeLink');
    const verificationMessageArea = document.getElementById('verificationMessageArea'); // Expected message area ID

    /**
     * Displays a message in the verificationMessageArea with appropriate styling.
     * @param {string} message - The message to display.
     * @param {'success' | 'error' | 'info'} type - The type of message to determine styling.
     */
    function displayVerificationMessage(message, type = 'info') {
        if (verificationMessageArea) { // Check if element exists
            verificationMessageArea.textContent = message;
            let className = 'mb-4 text-sm text-center';
            if (type === 'success') {
                className += ' text-green-600';
            } else if (type === 'error') {
                className += ' text-red-600';
            } else { // info or default
                className += ' text-gray-700';
            }
            verificationMessageArea.className = className;
        } else {
            console.error("verificationMessageArea not found in DOM for message:", message);
        }
    }

    if (verifyEmailForm) { // Check if form element exists
        verifyEmailForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            displayVerificationMessage('Verifying code...', 'info');
            if (verifyButton) verifyButton.disabled = true;

            const email = emailInput ? emailInput.value.trim() : ''; // Handle if emailInput is null
            const code = verificationCodeInput ? verificationCodeInput.value.trim() : ''; // Handle if codeInput is null

            if (!email) {
                displayVerificationMessage('Email address is missing from the form.', 'error');
                if (verifyButton) verifyButton.disabled = false;
                return;
            }
            if (!code || !/^\d{6}$/.test(code)) { // Check if code is 6 digits
                 displayVerificationMessage('Please enter a valid 6-digit verification code.', 'error');
                 if (verifyButton) verifyButton.disabled = false;
                 return;
            }


            const formData = new FormData();
            formData.append('email', email);
            formData.append('code', code); // 'code' matches new backend expectation from JS

            try {
                const response = await fetch('actions/verify_email_action.php', { // New backend endpoint
                    method: 'POST',
                    body: formData
                });
                // Try to parse JSON regardless of response.ok, as server might send JSON error details
                const data = await response.json().catch(parseError => {
                    console.error("Failed to parse JSON response:", parseError);
                    throw new Error(`Server communication error. Status: ${response.status}. Please check console.`);
                });


                if (data.success) {
                    displayVerificationMessage(data.message || 'Verification successful!', 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                } else {
                    displayVerificationMessage(data.message || 'Verification failed.', 'error');
                }
            } catch (error) {
                console.error('Error during verification:', error);
                displayVerificationMessage(error.message || 'Network error during verification. Please try again.', 'error');
            } finally {
                if (verifyButton) verifyButton.disabled = false;
            }
        });
    } else {
        console.error("verifyEmailForm not found in DOM.");
    }

    if (resendCodeLink) { // Check if resend link exists
        resendCodeLink.addEventListener('click', async function (e) {
            e.preventDefault();
            displayVerificationMessage('Resending code...', 'info');
            resendCodeLink.style.pointerEvents = 'none'; // More reliable way to disable link
            resendCodeLink.classList.add('opacity-50');


            const email = emailInput ? emailInput.value.trim() : '';
            if (!email) {
                displayVerificationMessage('Email address is missing. Cannot resend code.', 'error');
                resendCodeLink.style.pointerEvents = 'auto';
                resendCodeLink.classList.remove('opacity-50');
                return;
            }

            const formData = new FormData();
            formData.append('email', email);

            try {
                const response = await fetch('actions/resend_verification_action.php', { // New backend endpoint
                    method: 'POST',
                    body: formData
                });
                 const data = await response.json().catch(parseError => {
                    console.error("Failed to parse JSON response (resend):", parseError);
                    throw new Error(`Server communication error (resend). Status: ${response.status}. Please check console.`);
                });

                if (data.success) {
                    displayVerificationMessage(data.message || 'New code sent! Check your email.', 'success');
                } else {
                    displayVerificationMessage(data.message || 'Failed to resend code.', 'error');
                }
            } catch (error) {
                console.error('Error resending code:', error);
                displayVerificationMessage(error.message ||'Network error resending code. Please try again.', 'error');
            } finally {
                setTimeout(() => {
                    resendCodeLink.style.pointerEvents = 'auto';
                    resendCodeLink.classList.remove('opacity-50');
                }, 30000); // Wait 30 seconds before allowing resend
            }
        });
    } else {
        console.warn("resendCodeLink not found in DOM.");
    }
});
