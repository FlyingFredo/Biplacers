// Main javascript file
console.log("Main JS loaded. Base URL: " + (typeof BASE_URL !== 'undefined' ? BASE_URL : 'Not Set'));

// Example: Add a class to the body after page load
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('js-loaded');
});
