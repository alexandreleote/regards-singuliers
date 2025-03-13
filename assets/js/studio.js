function handleStudioMoreClick(event) {
    const studioMoreContent = event.target.closest('.studio-more').querySelector('.studio-more-content');
    if (studioMoreContent) {
        studioMoreContent.classList.toggle('visible');
        event.target.classList.toggle('active');
    }
}

function initializeStudioMore() {
    const studioMore = document.querySelector('.studio-more h3');
    const studioMoreContent = document.querySelector('.studio-more-content');
    
    if (studioMore && studioMoreContent) {
        studioMoreContent.classList.remove('visible');
        studioMore.classList.remove('active');
        studioMore.removeEventListener('click', handleStudioMoreClick);
        studioMore.addEventListener('click', handleStudioMoreClick);
    }
}

// Initialize on both DOMContentLoaded and Turbo navigation
document.addEventListener('DOMContentLoaded', initializeStudioMore);
document.addEventListener('turbo:load', initializeStudioMore);
document.addEventListener('turbo:render', initializeStudioMore);
