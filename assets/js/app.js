document.addEventListener('DOMContentLoaded', () => {
  // Auto-hide toasts
  document.querySelectorAll('.toast').forEach(toast => {
    setTimeout(() => toast.remove(), 2500);
  });
});
