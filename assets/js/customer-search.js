// Live search-as-you-type for the Customers page.
// Debounces keystrokes, fetches matching rows from ajax/search_customers.php,
// and swaps the <tbody> content. No page reload needed.

document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('customerSearch');
    var tableBody   = document.getElementById('customerTableBody');
    var noResults   = document.getElementById('noResults');

    if (!searchInput || !tableBody) {
        return; // not on the customers page
    }

    var debounceTimer = null;

    searchInput.addEventListener('keyup', function () {
        clearTimeout(debounceTimer);
        var term = searchInput.value.trim();

        debounceTimer = setTimeout(function () {
            fetch('ajax/search_customers.php?q=' + encodeURIComponent(term))
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }
                    return response.text();
                })
                .then(function (html) {
                    tableBody.innerHTML = html;
                    noResults.style.display = html.trim() === '' ? 'block' : 'none';
                })
                .catch(function () {
                    // Fail quietly - user can still use the full list / retry search.
                    noResults.style.display = 'none';
                });
        }, 300); // 300ms debounce
    });
});