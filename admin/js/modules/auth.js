export function initAuth() {
    initPasswordMatchValidation();
    initPasswordStrength();
    initUsernameValidation();
    initPasswordVisibilityToggle();
    initPasswordGenerator();
    initSessionTimer();
}

function initPasswordMatchValidation() {
    const setupMatchValidation = (pwdId, repeatId, errorMsg) => {
        const pwd = document.getElementById(pwdId);
        const repeat = document.getElementById(repeatId);
        if (!pwd || !repeat) return;

        const validate = () => {
            if (repeat.value !== pwd.value) {
                repeat.setCustomValidity(errorMsg);
            } else {
                repeat.setCustomValidity('');
            }
        };

        pwd.addEventListener('input', validate);
        repeat.addEventListener('input', validate);
    };

    // Form 1: Change currently logged admin password
    setupMatchValidation('new_password', 'repeat_new_password', 'Hasła nie są identyczne');
    
    // Form 2: Add new admin user
    setupMatchValidation('password', 'repeat_password', 'Hasła nie są identyczne');
}

function initPasswordStrength() {
    const checkStrength = (password) => {
        let score = 0;
        if (!password) return 0;

        // length check
        if (password.length >= 8) score += 25;
        else if (password.length >= 6) score += 15;

        // upper and lowercase
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 25;

        // numbers
        if (/\d/.test(password)) score += 25;

        // special characters
        if (/[^A-Za-z0-9]/.test(password)) score += 25;

        return score;
    };

    const setupStrengthIndicator = (pwdId) => {
        const pwdInput = document.getElementById(pwdId);
        const container = document.getElementById(`${pwdId}-strength-container`);
        const bar = document.getElementById(`${pwdId}-strength-bar`);
        const text = document.getElementById(`${pwdId}-strength-text`);

        if (!pwdInput || !container || !bar || !text) return;

        pwdInput.addEventListener('input', () => {
            const val = pwdInput.value;
            if (!val) {
                container.classList.add('d-none');
                return;
            }

            container.classList.remove('d-none');
            const score = checkStrength(val);

            bar.style.width = `${score}%`;

            // Update styling based on score
            bar.classList.remove('bg-danger', 'bg-warning', 'bg-info', 'bg-success');
            text.classList.remove('text-danger', 'text-warning', 'text-info', 'text-success');

            if (score <= 25) {
                bar.classList.add('bg-danger');
                text.classList.add('text-danger');
                text.textContent = 'Bardzo słabe';
            } else if (score <= 50) {
                bar.classList.add('bg-warning');
                text.classList.add('text-warning');
                text.textContent = 'Słabe';
            } else if (score <= 75) {
                bar.classList.add('bg-info');
                text.classList.add('text-info');
                text.textContent = 'Dobre';
            } else {
                bar.classList.add('bg-success');
                text.classList.add('text-success');
                text.textContent = 'Silne';
            }
        });
    };

    setupStrengthIndicator('new_password');
    setupStrengthIndicator('password');
}

function initUsernameValidation() {
    const setupUsernameValidation = (id) => {
        const input = document.getElementById(id);
        if (!input) return;

        input.addEventListener('input', () => {
            const val = input.value;
            const clean = val.replace(/[^a-zA-Z0-9_\-]/g, '');
            
            if (val !== clean) {
                input.value = clean;
                input.setCustomValidity('Nazwa użytkownika może zawierać tylko litery, cyfry, podkreślenia (_) i myślniki (-).');
                input.reportValidity();
            } else {
                input.setCustomValidity('');
            }
        });
    };

    setupUsernameValidation('new_username');
    setupUsernameValidation('username');
}

function initSessionTimer() {
    const display = document.getElementById('session-time-display');
    if (!display) return;
    
    // Czas trwania sesji to 30 minut = 1800 sekund
    let timeLeft = 1800;
    
    const updateDisplay = () => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 60) {
            const badge = display.closest('.badge');
            if (badge) {
                badge.classList.remove('bg-secondary-subtle', 'text-secondary', 'border-secondary-subtle');
                badge.classList.add('bg-danger', 'text-white', 'border-danger');
            }
        }
    };
    
    updateDisplay();
    
    const timer = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(timer);
            window.location.reload(); // Odświeżenie strony wymusi wylogowanie przez backend (timeout)
        } else {
            updateDisplay();
        }
    }, 1000);

    // Opcjonalne udostępnienie metody do resetowania timera (np. po AJAX)
    window.resetSessionTimer = () => {
        timeLeft = 1800;
        const badge = display.closest('.badge');
        if (badge) {
            badge.classList.remove('bg-danger', 'text-white', 'border-danger');
            badge.classList.add('bg-secondary-subtle', 'text-secondary', 'border-secondary-subtle');
        }
        updateDisplay();
    };
}

function initPasswordVisibilityToggle() {
    const passwordFields = ['new_password', 'repeat_new_password', 'password', 'repeat_password'];
    passwordFields.forEach(id => {
        const toggleBtn = document.getElementById(`toggle_${id}`);
        const input = document.getElementById(id);
        if (!toggleBtn || !input) return;
        
        toggleBtn.addEventListener('click', () => {
            const icon = toggleBtn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });
}

function generateSecurePassword(length = 16) {
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const numbers = '0123456789';
    const symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    const allChars = uppercase + lowercase + numbers + symbols;
    
    let password = '';
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += lowercase[Math.floor(Math.random() * lowercase.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += symbols[Math.floor(Math.random() * symbols.length)];
    
    for (let i = 4; i < length; i++) {
        password += allChars[Math.floor(Math.random() * allChars.length)];
    }
    
    return password.split('').sort(() => 0.5 - Math.random()).join('');
}

function initPasswordGenerator() {
    const setupGenerator = (genBtnId, pwdId, repeatId) => {
        const btn = document.getElementById(genBtnId);
        const pwdInput = document.getElementById(pwdId);
        const repeatInput = document.getElementById(repeatId);
        if (!btn || !pwdInput || !repeatInput) return;
        
        btn.addEventListener('click', () => {
            const newPassword = generateSecurePassword();
            
            pwdInput.value = newPassword;
            repeatInput.value = newPassword;
            
            pwdInput.type = 'text';
            repeatInput.type = 'text';
            
            ['toggle_' + pwdId, 'toggle_' + repeatId].forEach(toggleId => {
                const toggleBtn = document.getElementById(toggleId);
                if (toggleBtn) {
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                }
            });
            
            pwdInput.dispatchEvent(new Event('input'));
            repeatInput.dispatchEvent(new Event('input'));
        });
    };
    
    setupGenerator('generate_new_password', 'new_password', 'repeat_new_password');
    setupGenerator('generate_password', 'password', 'repeat_password');
}
