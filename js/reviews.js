function matchReviewHeights() {
    const reviewCards = document.querySelectorAll('.review-content');
    let maxHeight = 0;

    // Reset heights first
    reviewCards.forEach(card => {
        card.style.height = 'auto';
    });

    // Get the maximum height
    reviewCards.forEach(card => {
        const height = card.offsetHeight;
        maxHeight = Math.max(maxHeight, height);
    });

    // Set all cards to the maximum height
    reviewCards.forEach(card => {
        card.style.height = `${maxHeight}px`;
        card.style.margin = '0 3px 5px 3px';
    });
}

// Run on page load and window resize
document.addEventListener('DOMContentLoaded', matchReviewHeights);
window.addEventListener('resize', matchReviewHeights); 