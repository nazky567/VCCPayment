/**
 * Main application client-side script
 */

document.addEventListener('DOMContentLoaded', function () {
    // Enable Bootstrap tooltips if they are used
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        if (typeof bootstrap !== 'undefined') {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        }
    });

    // Form Client-Side Validation
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (event) {
            let isValid = true;
            
            const emailInput = document.getElementById('customer_email');
            if (emailInput && !validateEmail(emailInput.value)) {
                showInputError(emailInput, 'Silakan masukkan alamat email yang valid.');
                isValid = false;
            } else if (emailInput) {
                clearInputError(emailInput);
            }

            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value.trim().length < 9) {
                showInputError(phoneInput, 'Nomor HP minimal terdiri dari 9 karakter.');
                isValid = false;
            } else if (phoneInput) {
                clearInputError(phoneInput);
            }

            const amountInput = document.getElementById('amount');
            if (amountInput && (parseFloat(amountInput.value) <= 0 || isNaN(parseFloat(amountInput.value)))) {
                showInputError(amountInput, 'Harga produk harus bernilai lebih dari 0.');
                isValid = false;
            } else if (amountInput) {
                clearInputError(amountInput);
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                const submitBtn = document.getElementById('btnSubmitPay');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses Transaksi...';
                }
            }
        });
    }

    // Helper functions
    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    }

    function showInputError(inputEl, message) {
        inputEl.classList.add('is-invalid');
        let feedbackEl = inputEl.nextElementSibling;
        if (!feedbackEl || !feedbackEl.classList.contains('invalid-feedback')) {
            feedbackEl = document.createElement('div');
            feedbackEl.classList.add('invalid-feedback');
            inputEl.parentNode.insertBefore(feedbackEl, inputEl.nextSibling);
        }
        feedbackEl.textContent = message;
    }

    function clearInputError(inputEl) {
        inputEl.classList.remove('is-invalid');
        const feedbackEl = inputEl.nextElementSibling;
        if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
            feedbackEl.remove();
        }
    }
});
