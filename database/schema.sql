document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.querySelector('.toast');
    if (toastEl) {
        setTimeout(() => {
            const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
            toast.hide();
        }, 4000);
    }
});
