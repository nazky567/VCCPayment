/**
 * Main application client-side script
 * CloudPay AI Subscriptions
 */

// Global function to allow HTML onclick events to trigger plan selection
function selectPlan(card) {
    const plan = card.getAttribute('data-plan');
    const price = card.getAttribute('data-price');
    const name = card.getAttribute('data-name');
    
    // Remove active state from all cards
    document.querySelectorAll('.pricing-card').forEach(c => c.classList.remove('active'));
    // Add active state to clicked card
    card.classList.add('active');
    
    // Update select dropdown
    const select = document.getElementById('product_select');
    if (select) {
        select.value = plan;
        
        // Trigger select change event to update database input fields
        const amountInput = document.getElementById('amount');
        const customProductField = document.getElementById('custom_product_field');
        const productNameInput = document.getElementById('product_name');
        const summaryProductName = document.getElementById('summary_product_name');
        const summarySubtotal = document.getElementById('summary_subtotal');
        const summaryTotal = document.getElementById('summary_total');
        
        amountInput.readOnly = true;
        amountInput.value = price;
        customProductField.style.display = 'none';
        document.getElementById('product_name_custom').required = false;
        
        productNameInput.value = name;
        summaryProductName.textContent = name;
        summarySubtotal.textContent = 'Rp ' + parseFloat(price).toLocaleString('id-ID');
        summaryTotal.textContent = 'Rp ' + parseFloat(price).toLocaleString('id-ID');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Default Selected Pricing Card UI
    const defaultSelect = document.getElementById('product_select');
    if (defaultSelect) {
        const defaultVal = defaultSelect.value;
        const defaultCard = document.querySelector(`.pricing-card[data-plan="${defaultVal}"]`);
        if (defaultCard) {
            defaultCard.classList.add('active');
        }
    }

    // 2. 3D Tilt Hover Effect for Pricing Cards
    document.querySelectorAll('.pricing-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const xc = rect.width / 2;
            const yc = rect.height / 2;
            const angleX = (yc - y) / 12; // tilt vertical
            const angleY = (x - xc) / 12; // tilt horizontal
            card.style.transform = `perspective(1000px) rotateX(${angleX}deg) rotateY(${angleY}deg) scale3d(1.02, 1.02, 1.02)`;
            
            // Subtle dynamic shadow based on mouse position
            card.style.boxShadow = `${-angleY * 1.5}px ${-angleX * 1.5}px 25px rgba(139, 92, 246, 0.25)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
            card.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.25)';
        });
    });

    // 3. Smooth Entry Micro-Animations
    document.querySelectorAll('.pricing-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'transform 0.4s ease, box-shadow 0.4s ease, border-color 0.4s ease, opacity 0.4s ease';
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    // 4. Custom Toast Notification helper
    function showToast(message, type = 'danger') {
        const toastEl = document.getElementById('validationToast');
        if (toastEl) {
            const toastMessage = document.getElementById('toastMessage');
            toastMessage.textContent = message;
            
            // Adjust colors based on success/danger
            if (type === 'success') {
                toastEl.style.background = 'rgba(16, 185, 129, 0.95)';
                toastEl.classList.remove('bg-danger');
                toastEl.classList.add('bg-success');
            } else {
                toastEl.style.background = 'rgba(239, 68, 68, 0.95)';
                toastEl.classList.remove('bg-success');
                toastEl.classList.add('bg-danger');
            }
            
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
        } else {
            // Fallback to standard alert if toast element isn't in DOM
            alert(message);
        }
    }

    // 5. Form Client-Side Validation with Animated Toast
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (event) {
            let isValid = true;
            let errorMessage = '';
            
            const nameInput = document.getElementById('customer_name');
            if (nameInput && nameInput.value.trim() === '') {
                errorMessage = 'Nama Lengkap tidak boleh kosong.';
                nameInput.classList.add('is-invalid');
                isValid = false;
            } else if (nameInput) {
                nameInput.classList.remove('is-invalid');
            }

            const emailInput = document.getElementById('customer_email');
            if (isValid && emailInput && !validateEmail(emailInput.value)) {
                errorMessage = 'Silakan masukkan alamat email yang valid.';
                emailInput.classList.add('is-invalid');
                isValid = false;
            } else if (emailInput) {
                emailInput.classList.remove('is-invalid');
            }

            const phoneInput = document.getElementById('customer_phone');
            if (isValid && phoneInput && phoneInput.value.trim().length < 9) {
                errorMessage = 'Nomor HP minimal terdiri dari 9 karakter.';
                phoneInput.classList.add('is-invalid');
                isValid = false;
            } else if (phoneInput) {
                phoneInput.classList.remove('is-invalid');
            }

            const amountInput = document.getElementById('amount');
            if (isValid && amountInput && (parseFloat(amountInput.value) <= 0 || isNaN(parseFloat(amountInput.value)))) {
                errorMessage = 'Harga produk harus bernilai lebih dari 0.';
                amountInput.classList.add('is-invalid');
                isValid = false;
            } else if (amountInput) {
                amountInput.classList.remove('is-invalid');
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                showToast(errorMessage, 'danger');
            } else {
                const submitBtn = document.getElementById('btnSubmitPay');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghubungkan Secure Sandbox...';
                }
            }
        });
    }

    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    }
});
