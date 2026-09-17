document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('jobcard-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const customer = document.getElementById('customer_id').value;
        const device = document.getElementById('device_name').value.trim();
        const complaint = document.getElementById('complaint').value.trim();
        const estimate = document.getElementById('estimate').value;

        let message = '';

        if (customer === '') {
            message = 'Please select a customer.';
        } else if (device === '') {
            message = 'Device name is required.';
        } else if (complaint === '') {
            message = 'Complaint is required.';
        } else if (estimate === '' || isNaN(estimate) || Number(estimate) < 0) {
            message = 'Estimate must be a valid non-negative number.';
        }

        if (message !== '') {
            e.preventDefault();
            alert(message);
        }
    });
});