// job-print.js - print button + printed date/time stamp (Day 8)
document.addEventListener('DOMContentLoaded', function () {
    var stamp = document.getElementById('printedOn');

    // Uses the viewer's local date/time, refreshed right before printing
    function updateStamp() {
        if (stamp) {
            stamp.textContent = new Date().toLocaleString();
        }
    }

    updateStamp();
    window.addEventListener('beforeprint', updateStamp);

    var btn = document.getElementById('printBtn');
    if (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    }
});