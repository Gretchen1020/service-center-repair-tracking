// Modern browsers can restore a page from the back/forward cache (bfcache)
// when the user clicks Back/Forward, without asking the server again.
// This can briefly show a frozen snapshot of the page even after logout.
// If this page is restored from bfcache, force a real reload so
// requireLogin() runs again. (Note: doesn't fully eliminate this in every
// browser — see Testing Log, test SC-03b, for the documented limitation.)
window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});