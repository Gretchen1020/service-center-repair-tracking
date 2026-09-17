// Live search-as-you-type for the Job List page.
// Debounces keystrokes, fetches matching rows from ajax/search_jobs.php,
// and swaps the <tbody> content. No page reload needed.
// Mirrors assets/js/customer-search.js.

document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('jobSearch');
    var tableBody   = document.getElementById('jobTableBody');
    var noResults   = document.getElementById('noJobResults');

    if (!searchInput || !tableBody) {
        return; // not on the job list page
    }

    var debounceTimer = null;

    searchInput.addEventListener('keyup', function () {
        clearTimeout(debounceTimer);
        var term = searchInput.value.trim();

        debounceTimer = setTimeout(function () {
            fetch('ajax/search_jobs.php?q=' + encodeURIComponent(term))
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }
                    return response.text();
                })
                .then(function (html) {
                    tableBody.innerHTML = html;
                    noResults.style.display = tableBody.querySelector('tr') ? 'none' : 'block';
                })
                .catch(function () {
                    // Fail quietly - user can still use the full list / retry search.
                    noResults.style.display = 'none';
                });
        }, 300); // 300ms debounce
    });
});