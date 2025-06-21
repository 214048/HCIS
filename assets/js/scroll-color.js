document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const colors = [
        ['#4f46e5', '#7c3aed'], // Purple
        ['#3b82f6', '#60a5fa'], // Blue
        ['#10b981', '#34d399'], // Green
        ['#f59e0b', '#fbbf24'], // Yellow
        ['#ef4444', '#f87171']  // Red
    ];
    let currentColorIndex = 0;

    function updateBackgroundColor() {
        const scrollPosition = window.pageYOffset;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        // Calculate progress through the page
        const progress = scrollPosition / (documentHeight - windowHeight);
        
        // Update color index based on progress
        currentColorIndex = Math.floor(progress * colors.length) % colors.length;
        
        // Get the current color pair
        const [startColor, endColor] = colors[currentColorIndex];
        
        // Calculate the gradient position based on progress
        const gradientPosition = (progress * 100) + '%';
        
        // Update the background
        body.style.background = `linear-gradient(135deg, ${startColor} ${gradientPosition}, ${endColor} ${gradientPosition})`;
        
        // Update the hero section background
        const hero = document.querySelector('.page-hero');
        if (hero) {
            hero.style.background = `linear-gradient(135deg, ${startColor} ${gradientPosition}, ${endColor} ${gradientPosition})`;
        }
    }

    // Update on scroll
    window.addEventListener('scroll', updateBackgroundColor);
    
    // Initial update
    updateBackgroundColor();
});
