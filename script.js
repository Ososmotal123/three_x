// Set current year in footer if placeholder exists
const yearElement = document.getElementById("year");
if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
}

// Mobile navigation toggle
const navToggle = document.getElementById("navToggle");
const mobileNav = document.getElementById("mobileNav");

if (navToggle && mobileNav) {
    navToggle.addEventListener("click", () => {
        mobileNav.classList.toggle("open");
    });

    // Close mobile nav when a link is clicked
    mobileNav.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            mobileNav.classList.remove("open");
        });
    });
}

// Simple front-end handling for the quote form
const quoteForm = document.getElementById("quoteForm");
const formMessage = document.getElementById("formMessage");

if (quoteForm && formMessage) {
    const namePattern = /^[\p{L}\s'.-]{2,}$/u;
    const phonePattern = /^\+?\d[\d\s-]{7,14}$/;
    const endpoint = quoteForm.getAttribute("action") || "submit_quote.php";
    const submitButton = quoteForm.querySelector('button[type="submit"]');
    const defaultButtonText = submitButton ? submitButton.textContent : "";
    const loadingButtonText = submitButton?.dataset.loadingText || "Sending...";
    const successMessage =
        quoteForm.dataset.successMessage ||
        "Thank you. Your request has been recorded. We will contact you as soon as possible.";
    const errorMessage =
        quoteForm.dataset.errorMessage || "We could not submit your request. Please try again.";
    const networkErrorMessage =
        quoteForm.dataset.networkErrorMessage || "A network error occurred. Please try again in a moment.";

    const showMessage = (text, isError = true) => {
        formMessage.textContent = text;
        formMessage.style.color = isError ? "#f97316" : "#22c55e";
    };

    const toggleSubmittingState = (isSubmitting) => {
        if (submitButton) {
            submitButton.disabled = isSubmitting;
            submitButton.textContent = isSubmitting ? loadingButtonText : defaultButtonText;
        }
    };

    quoteForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const name = this.name.value.trim();
        const phone = this.phone.value.trim();
        const area = this.area.value.trim();
        const service = this.service.value;
        const message = this.message.value.trim();

        if (!namePattern.test(name)) {
            showMessage("Please enter your full name using letters only.");
            return;
        }

        if (!phonePattern.test(phone)) {
            showMessage("Enter a valid phone or WhatsApp number (e.g., +966 50 420 2782).");
            return;
        }

        if (area.length < 2) {
            showMessage("Specify the Riyadh district or neighborhood for your project.");
            return;
        }

        if (!service) {
            showMessage("Select the service type that best matches your request.");
            return;
        }

        if (message.length < 10) {
            showMessage("Share more project details so we can prepare your quote.");
            return;
        }

        toggleSubmittingState(true);

        try {
            const response = await fetch(endpoint, {
                method: "POST",
                body: new FormData(this),
            });

            const payload = await response.json().catch(() => null);
            const success = response.ok && payload?.success;
            const feedback = payload?.message || (success ? successMessage : errorMessage);

            showMessage(feedback, !success);

            if (success) {
                this.reset();
            }
        } catch (error) {
            showMessage(networkErrorMessage);
        } finally {
            toggleSubmittingState(false);
        }
    });
}
