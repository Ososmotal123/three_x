const yearElementAr = document.getElementById("year");
if (yearElementAr) {
    yearElementAr.textContent = new Date().getFullYear();
}

const navToggleAr = document.getElementById("navToggle");
const mobileNavAr = document.getElementById("mobileNav");

if (navToggleAr && mobileNavAr) {
    navToggleAr.addEventListener("click", () => {
        mobileNavAr.classList.toggle("open");
    });

    mobileNavAr.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            mobileNavAr.classList.remove("open");
        });
    });
}

const quoteFormAr = document.getElementById("quoteForm");
const formMessageAr = document.getElementById("formMessage");

if (quoteFormAr && formMessageAr) {
    const namePattern = /^[\p{L}\s'.-]{2,}$/u;
    const phonePattern = /^\+?\d[\d\s-]{7,14}$/;
    const endpoint = quoteFormAr.getAttribute("action") || "submit_quote.php";
    const submitButton = quoteFormAr.querySelector('button[type="submit"]');
    const defaultButtonText = submitButton ? submitButton.textContent : "";
    const loadingButtonText = submitButton?.dataset.loadingText || "جارٍ الإرسال...";
    const successMessage =
        quoteFormAr.dataset.successMessage || "تم تسجيل طلبك. سنتواصل معك في أقرب وقت ممكن.";
    const errorMessage =
        quoteFormAr.dataset.errorMessage || "تعذر إرسال طلبك. حاول مرة أخرى.";
    const networkErrorMessage =
        quoteFormAr.dataset.networkErrorMessage || "حدث خطأ في الاتصال. يرجى المحاولة بعد قليل.";

    const showMessage = (text, isError = true) => {
        formMessageAr.textContent = text;
        formMessageAr.style.color = isError ? "#f97316" : "#22c55e";
    };

    const toggleSubmittingState = (isSubmitting) => {
        if (submitButton) {
            submitButton.disabled = isSubmitting;
            submitButton.textContent = isSubmitting ? loadingButtonText : defaultButtonText;
        }
    };

    quoteFormAr.addEventListener("submit", async function (e) {
        e.preventDefault();

        const name = this.name.value.trim();
        const phone = this.phone.value.trim();
        const area = this.area.value.trim();
        const service = this.service.value;
        const message = this.message.value.trim();

        if (!namePattern.test(name)) {
            showMessage("يرجى إدخال اسمك الكامل باستخدام أحرف فقط.");
            return;
        }

        if (!phonePattern.test(phone)) {
            showMessage("أدخل رقم هاتف أو واتساب صالح (مثال: +966 50 420 2782).");
            return;
        }

        if (area.length < 2) {
            showMessage("اذكر الحي أو المنطقة في الرياض الخاصة بالمشروع.");
            return;
        }

        if (!service) {
            showMessage("اختر نوع الخدمة المناسبة لطلبك.");
            return;
        }

        if (message.length < 10) {
            showMessage("شارك المزيد من تفاصيل المشروع حتى نتمكن من تجهيز العرض.");
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
