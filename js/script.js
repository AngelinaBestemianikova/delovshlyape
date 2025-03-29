// Burger menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const burgerMenu = document.querySelector('.burger-menu');
    const navLinks = document.querySelector('.nav-links');
    
    burgerMenu.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        burgerMenu.classList.toggle('active');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!navLinks.contains(event.target) && !burgerMenu.contains(event.target)) {
            navLinks.classList.remove('active');
            burgerMenu.classList.remove('active');
        }
    });

    // Dropdown menu functionality
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const header = item.querySelector('.nav-item-header');
        
        header.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            item.classList.toggle('active');
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-item')) {
            navItems.forEach(item => {
                item.classList.remove('active');
            });
        }
    });
});

