// Confirms before submitting a status change on job_details.php.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('statusForm');
    var select = document.getElementById('statusSelect');
    if (!form || !select) {
        return;
    }

    form.addEventListener('submit', function (e) {
        var chosen = select.options[select.selectedIndex].text;
        if (!confirm('Change job status to "' + chosen + '"?')) {
            e.preventDefault();
        }
    });
});