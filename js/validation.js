/**
 * QUIZARENA — Client-side Form Validation
 * Validates registration, login, quiz creation, and question forms.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Registration Form Validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const studentFields = document.getElementById('studentFields');
        const teacherFields = document.getElementById('teacherFields');
        const studentIdInput = document.getElementById('student_id');
        const courseInput = document.getElementById('course');
        const departmentInput = document.getElementById('department');

        // Toggle role-specific input fields
        function updateRoleFields() {
            const selectedRole = document.querySelector('input[name="role"]:checked')?.value;
            if (selectedRole === 'student') {
                if (studentFields) studentFields.classList.remove('d-none');
                if (teacherFields) teacherFields.classList.add('d-none');
                if (studentIdInput) studentIdInput.required = true;
                if (courseInput) courseInput.required = true;
                if (departmentInput) departmentInput.required = false;
            } else if (selectedRole === 'teacher') {
                if (studentFields) studentFields.classList.add('d-none');
                if (teacherFields) teacherFields.classList.remove('d-none');
                if (studentIdInput) studentIdInput.required = false;
                if (courseInput) courseInput.required = false;
                if (departmentInput) departmentInput.required = true;
            }
        }

        roleInputs.forEach(radio => radio.addEventListener('change', updateRoleFields));
        updateRoleFields();

        // Password matching validation
        registerForm.addEventListener('submit', (e) => {
            if (passwordInput && confirmPasswordInput) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                    confirmPasswordInput.reportValidity();
                    return false;
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }

            if (!registerForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            registerForm.classList.add('was-validated');
        });

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', () => {
                if (passwordInput && confirmPasswordInput.value !== passwordInput.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            });
        }
    }

    // 2. Quiz Creation Form Validation
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        const startInput = document.getElementById('start_datetime');
        const endInput = document.getElementById('end_datetime');

        quizForm.addEventListener('submit', (e) => {
            if (startInput && endInput) {
                const startDate = new Date(startInput.value);
                const endDate = new Date(endInput.value);

                if (endDate <= startDate) {
                    e.preventDefault();
                    endInput.setCustomValidity('End date/time must be strictly after the start date/time');
                    endInput.reportValidity();
                    return false;
                } else {
                    endInput.setCustomValidity('');
                }
            }

            if (!quizForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            quizForm.classList.add('was-validated');
        });
    }

    // 3. Question Form Validation
    const questionForm = document.getElementById('questionForm');
    if (questionForm) {
        questionForm.addEventListener('submit', (e) => {
            const timeLimit = document.getElementById('time_limit');
            if (timeLimit && parseInt(timeLimit.value) < 10) {
                e.preventDefault();
                timeLimit.setCustomValidity('Question time limit must be at least 10 seconds');
                timeLimit.reportValidity();
                return false;
            } else if (timeLimit) {
                timeLimit.setCustomValidity('');
            }

            if (!questionForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            questionForm.classList.add('was-validated');
        });
    }
});
