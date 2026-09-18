// Live search-as-you-type for the Job List page.
// Debounces keystrokes, fetches matching rows from ajax/search_jobs.php,
// and swaps the <tbody> content. No page reload needed.
// Mirrors assets/js/customer-search.js.

document.addEventListener('DOMContentLoaded', function () {
    var searchInput   = document.getElementById('jobSearch');
    var tableBody     = document.getElementById('jobTableBody');
    var noResults     = document.getElementById('noJobResults');
    var statusSelect  = document.getElementById('statusFilterSelect');

    if (!searchInput || !tableBody) {
        return; // not on the job list page
    }

    var debounceTimer     = null;
    var currentController = null; // tracks the in-flight request, if any

    searchInput.addEventListener('keyup', function () {
        clearTimeout(debounceTimer);
        var term   = searchInput.value.trim();
        var status = statusSelect ? statusSelect.value : '';

        debounceTimer = setTimeout(function () {
            // Cancel any request still in flight so a slower, older
            // response can never land after a newer one and overwrite it.
            if (currentController) {
                currentController.abort();
            }
            currentController = new AbortController();

            var url = 'ajax/search_jobs.php?q=' + encodeURIComponent(term);
            if (status) {
                url += '&status=' + encodeURIComponent(status);
            }

            fetch(url, { signal: currentController.signal })
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
                .catch(function (err) {
                    if (err.name === 'AbortError') {
                        return; // superseded by a newer keystroke - ignore
                    }
                    // Fail quietly - user can still use the full list / retry search.
                    noResults.style.display = 'none';
                });
        }, 300); // 300ms debounce
    });
});