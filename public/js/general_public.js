//////////for loading animation on going back to prev. pages
document.addEventListener('DOMContentLoaded', function () {
    // Select all links with the "back-page" class
    const backPageLinks = document.querySelectorAll(".back-page");

    // Add event listener for each back link
    backPageLinks.forEach(function(link) {
        link.addEventListener("click", function (e) {
        e.preventDefault(); // Prevent default link behavior to show the loading animation

        NProgress.start(); // Start the loading bar animation

        setTimeout(() => {
            NProgress.set(0.7); // Move the bar to 70%
        }, 300); // Adjust timing here

        setTimeout(() => {
            NProgress.done(); // Finish the loading bar animation
            window.location.href = link.href; // Redirect after loading bar completes
        }, 1200); // Ensure the redirect happens after the animation completes
    });
});

// Ensure NProgress finishes when the page loads
window.onload = function () {
    NProgress.done();
};
});