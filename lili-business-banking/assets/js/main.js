const loginForm = document.getElementById('lili-login-form');
if (loginForm) {
    const toggleLoading = (form, isLoading) => {
        if (isLoading) {
            form.classList.add('loading');
        } else {
            form.classList.remove('loading');
        }
    };

    const showError = (form, message) => {
        const existingErrors = form.querySelectorAll('.lili-message.error');
        existingErrors.forEach(error => error.remove());

        const errorDiv = document.createElement('div');
        errorDiv.className = 'lili-message error';
        errorDiv.innerHTML = `<p>${message}</p>`;
        form.insertBefore(errorDiv, form.firstChild);
    };

    const handleInitialLogin = async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);

        toggleLoading(loginForm, true);

        try {
            const response = await fetch(liliAjax.ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'lili_login',
                    username: formData.get('lili_username'),
                    password: formData.get('lili_password'),
                    login_nonce: liliAjax.login_nonce
                })
            });

            const data = await response.json();
            console.log('Login response:', data); // Debug log

            if (!data.success) {
                showError(loginForm, data.message || 'Login failed. Please try again.');
                toggleLoading(loginForm, false);
                return;
            }

            // Handle MFA challenge case
            if (data.challenge) {
                toggleLoading(loginForm, false);
                loginForm.outerHTML = `
                   <form id="lili-login-form" method="post" action="">
                       <div class="form_wrap">
                           <div class="form_wrap__title">
                               ${data.challenge.prompt}
                           </div>
                           <div class="form_wrap__otp_step">
                           ${data.challenge.send_methods.map(method => `
                               <div class="form_wrap__group">
                                   <label>
                                       <input type="radio" name="mfa_method" value="${method.id}" data-mask="${method.mask}" required>
                                       ${method.type.charAt(0).toUpperCase() + method.type.slice(1)} to ${method.mask}
                                   </label>
                               </div>
                           `).join('')}
                           </div>
                           <div class="form_wrap__group submit-wrap">
                               <input type="submit" name="lili_mfa_method_submit" class="button button-primary button-hero" value="Send Code">
                           </div>
                       </div>
                   </form>
               `;

                const newForm = document.getElementById('lili-login-form');
                let selectedMethodId = null;
                let selectedMethodMask = null;

                const handleMFASubmit = async (e) => {
                    e.preventDefault();
                    const formData = new FormData(newForm);
                    selectedMethodId = formData.get('mfa_method');

                    // Get the selected radio button and its mask
                    const selectedRadio = newForm.querySelector(`input[name="mfa_method"][value="${selectedMethodId}"]`);
                    selectedMethodMask = selectedRadio ? selectedRadio.getAttribute('data-mask') : '';

                    toggleLoading(newForm, true);

                    try {
                        const response = await fetch(liliAjax.ajaxurl, {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'lili_send_otp',
                                send_method_id: selectedMethodId,
                                send_otp_nonce: liliAjax.send_otp_nonce
                            })
                        });

                        const data = await response.json();

                        if (!data.success) {
                            showError(newForm, data.message);
                            toggleLoading(newForm, false);
                            return;
                        }

                        if (data.success) {
                            toggleLoading(newForm, false);
                            document.querySelector('.lili_login_form__title').textContent = 'Enter your verification code';
                            newForm.innerHTML = `
                               <div class="form_wrap">
                                   <div class="form_wrap__title">
                                       A one-time code was just sent to ${selectedMethodMask}. <br> Enter it here when it arrives.
                                   </div>
                                    <div class="form_wrap__group">
                                       <label>VERIFICATION CODE</label>
                                       <div class="otp-input-group">
                                           <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                           <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                           <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                           <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                           <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                           <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                                           <input type="hidden" name="otp_code" required>
                                       </div>
                                   </div>
                                   <div class="form_wrap__group submit-wrap otp">
                                       <input type="submit" name="lili_verify_otp_submit" class="button button-primary button-hero" value="Continue">
                                       <button type="button" class="button button-small button-secondary" id="resend-code">Resend Code</button>
                                   </div>
                               </div>
                           `;

                            // Update the login form title if it exists
                            const titleElement = document.querySelector('.lili_login_form__title');
                            if (titleElement) {
                                titleElement.textContent = 'Enter your verification code';
                            }

                            // find .form_wrap__group label and remove it
                            const label = newForm.querySelector('.form_wrap__group label');
                            if (label) {
                                label.remove();
                            }

                            // Handle OTP input behavior
                            const otpInputs = newForm.querySelectorAll('.otp-input');
                            const hiddenInput = newForm.querySelector('input[name="otp_code"]');

                            otpInputs.forEach((input, index) => {
                                // Handle input
                                input.addEventListener('input', (e) => {
                                    const value = e.target.value;

                                    // Only allow numbers
                                    if (!/^\d*$/.test(value)) {
                                        input.value = '';
                                        return;
                                    }

                                    // Move to next input if value is entered
                                    if (value && index < otpInputs.length - 1) {
                                        otpInputs[index + 1].focus();
                                    }

                                    // Update hidden input with complete value
                                    const completeValue = Array.from(otpInputs)
                                        .map(input => input.value)
                                        .join('');
                                    hiddenInput.value = completeValue;
                                });

                                // Handle backspace
                                input.addEventListener('keydown', (e) => {
                                    if (e.key === 'Backspace' && !input.value && index > 0) {
                                        otpInputs[index - 1].focus();
                                    }
                                });

                                // Handle paste
                                input.addEventListener('paste', (e) => {
                                    e.preventDefault();
                                    const pastedData = e.clipboardData.getData('text');
                                    const numbersOnly = pastedData.replace(/[^\d]/g, '').slice(0, 6);

                                    if (numbersOnly) {
                                        // Distribute the pasted numbers across inputs
                                        numbersOnly.split('').forEach((char, i) => {
                                            if (otpInputs[i]) {
                                                otpInputs[i].value = char;
                                            }
                                        });

                                        // Update hidden input
                                        hiddenInput.value = numbersOnly;

                                        // Focus the next empty input or the last input
                                        const nextEmptyIndex = Array.from(otpInputs).findIndex(input => !input.value);
                                        if (nextEmptyIndex !== -1) {
                                            otpInputs[nextEmptyIndex].focus();
                                        } else {
                                            otpInputs[otpInputs.length - 1].focus();
                                        }
                                    }
                                });
                            });

                            // Add handler for resend button
                            const resendButton = newForm.querySelector('#resend-code');
                            resendButton.addEventListener('click', async () => {
                                toggleLoading(newForm, true);
                                try {
                                    const response = await fetch(liliAjax.ajaxurl, {
                                        method: 'POST',
                                        body: new URLSearchParams({
                                            action: 'lili_send_otp',
                                            send_method_id: selectedMethodId
                                        })
                                    });

                                    const data = await response.json();

                                    if (!data.success) {
                                        showError(newForm, data.message);
                                    } else {
                                        // Show success message for resend
                                        const successDiv = document.createElement('div');
                                        successDiv.className = 'lili-message success';
                                        successDiv.innerHTML = '<p>Verification code has been resent.</p>';
                                        newForm.insertBefore(successDiv, newForm.firstChild);

                                        // Remove success message after 3 seconds
                                        setTimeout(() => {
                                            successDiv.remove();
                                        }, 3000);
                                    }
                                } catch (error) {
                                    showError(newForm, 'An error occurred while resending the code. Please try again.');
                                }
                                toggleLoading(newForm, false);
                            });

                            const handleOTPVerification = async (e) => {
                                e.preventDefault();
                                const formData = new FormData(newForm);
                                toggleLoading(newForm, true);

                                try {
                                    const response = await fetch(liliAjax.ajaxurl, {
                                        method: 'POST',
                                        body: new URLSearchParams({
                                            action: 'lili_verify_otp',
                                            otp_code: formData.get('otp_code'),
                                            validate_otp_nonce: liliAjax.validate_otp_nonce
                                        })
                                    });

                                    const data = await response.json();

                                    if (!data.success) {
                                        showError(newForm, data.message);
                                        toggleLoading(newForm, false);
                                        return;
                                    }

                                    if (data.success && data.data?.auth_token) {
                                        window.location.reload();
                                    } else {
                                        showError(newForm, 'Verification failed. Please try again.');
                                        toggleLoading(newForm, false);
                                    }

                                } catch (error) {
                                    showError(newForm, 'An error occurred during verification. Please try again.');
                                    toggleLoading(newForm, false);
                                }
                            };

                            newForm.addEventListener('submit', handleOTPVerification);
                        }

                    } catch (error) {
                        showError(newForm, 'An error occurred. Please try again.');
                        toggleLoading(newForm, false);
                    }
                };

                newForm.addEventListener('submit', handleMFASubmit);
            } else if (data.success) {
                // Show success message before reload
                const successDiv = document.createElement('div');
                successDiv.className = 'lili-message success';
                successDiv.innerHTML = '<p>Login successful! Redirecting...</p>';
                loginForm.insertBefore(successDiv, loginForm.firstChild);

                // Remove any existing error messages
                const existingErrors = loginForm.querySelectorAll('.lili-message.error');
                existingErrors.forEach(error => error.remove());

                toggleLoading(loginForm, false);

                // Small delay before reload to show the success message
                setTimeout(() => {
                    window.location.reload();
                }, 500);
                return;
            }

        } catch (error) {
            console.error('Login error:', error); // Debug log
            showError(loginForm, 'An error occurred. Please try again.');
            toggleLoading(loginForm, false);
        }
    };

    loginForm.addEventListener('submit', handleInitialLogin);
}

document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.tab-button');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Add active class to clicked tab and corresponding content
            tab.classList.add('active');
            document.getElementById(`${tab.dataset.tab}-content`).classList.add('active');
        });
    });
});