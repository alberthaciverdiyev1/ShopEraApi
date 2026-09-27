/**
 * Teymur Store - Main JavaScript
 * Handles navigation, accordion, modal, form validation, and animations
 */

(function() {
    'use strict';

    // ===========================
    // STATE MANAGEMENT
    // ===========================
    let currentlyOpenAccordion = null;
    let lastScrollPosition = 0;

    // ===========================
    // NAVIGATION
    // ===========================
    function initNavigation() {
        const nav = document.getElementById('nav');
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        const navLinks = document.querySelectorAll('.nav-link');

        // Toggle mobile menu
        navToggle.addEventListener('click', () => {
            const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', !isOpen);
            navMenu.classList.toggle('open');
        });

        // Close mobile menu when clicking a link
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navToggle.setAttribute('aria-expanded', 'false');
                navMenu.classList.remove('open');
            });
        });

        // Smooth scroll to sections
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                const targetSection = document.querySelector(targetId);

                if (targetSection) {
                    const headerOffset = 64; // var(--header-height)
                    const elementPosition = targetSection.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Update active nav link on scroll
        function updateActiveNavLink() {
            const sections = document.querySelectorAll('section[id]');
            const scrollPosition = window.pageYOffset + 100;

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                const sectionId = section.getAttribute('id');
                const navLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);

                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    navLinks.forEach(link => link.classList.remove('active'));
                    if (navLink) navLink.classList.add('active');
                }
            });
        }

        // Add shadow to nav on scroll
        function handleNavScroll() {
            const currentScrollPosition = window.pageYOffset;

            if (currentScrollPosition > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            lastScrollPosition = currentScrollPosition;
        }

        window.addEventListener('scroll', () => {
            updateActiveNavLink();
            handleNavScroll();
        });

        // Initial call
        updateActiveNavLink();
    }

    // ===========================
    // ACCORDION (FAQ)
    // ===========================
    function initAccordion() {
        const accordionButtons = document.querySelectorAll('.accordion-button');

        accordionButtons.forEach(button => {
            button.addEventListener('click', () => {
                const isExpanded = button.getAttribute('aria-expanded') === 'true';
                const contentId = button.getAttribute('aria-controls');
                const content = document.getElementById(contentId);

                // Close previously open accordion
                if (currentlyOpenAccordion && currentlyOpenAccordion !== button) {
                    currentlyOpenAccordion.setAttribute('aria-expanded', 'false');
                    const prevContentId = currentlyOpenAccordion.getAttribute('aria-controls');
                    const prevContent = document.getElementById(prevContentId);
                    if (prevContent) {
                        prevContent.classList.remove('open');
                    }
                }

                // Toggle current accordion
                if (isExpanded) {
                    button.setAttribute('aria-expanded', 'false');
                    content.classList.remove('open');
                    currentlyOpenAccordion = null;
                } else {
                    button.setAttribute('aria-expanded', 'true');
                    content.classList.add('open');
                    currentlyOpenAccordion = button;
                }
            });

            // Keyboard support
            button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });
    }

    // ===========================
    // MODAL (Privacy Policy)
    // ===========================
    function initModal() {
        const modal = document.getElementById('privacyModal');
        const modalOverlay = document.getElementById('modalOverlay');
        const modalClose = document.getElementById('modalClose');
        const privacyBtn = document.getElementById('privacyBtn');
        const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        let firstFocusableElement;
        let lastFocusableElement;
        let previousActiveElement;

        function openModal() {
            previousActiveElement = document.activeElement;
            modal.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';

            // Set focus trap
            const focusableContent = modal.querySelectorAll(focusableElements);
            firstFocusableElement = focusableContent[0];
            lastFocusableElement = focusableContent[focusableContent.length - 1];

            // Focus first element
            setTimeout(() => {
                modalClose.focus();
            }, 100);
        }

        function closeModal() {
            modal.setAttribute('hidden', '');
            document.body.style.overflow = '';

            // Restore focus
            if (previousActiveElement) {
                previousActiveElement.focus();
            }
        }

        // Open modal
        privacyBtn.addEventListener('click', openModal);

        // Close modal
        modalClose.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', closeModal);

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hasAttribute('hidden')) {
                closeModal();
            }
        });

        // Focus trap
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstFocusableElement) {
                        e.preventDefault();
                        lastFocusableElement.focus();
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastFocusableElement) {
                        e.preventDefault();
                        firstFocusableElement.focus();
                    }
                }
            }
        });
    }

    // ===========================
    // FORM VALIDATION & SUBMISSION
    // ===========================
    function initContactForm() {
        const form = document.getElementById('contactForm');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const messageInput = document.getElementById('message');
        const submitBtn = form.querySelector('.btn-submit');
        const successMessage = document.getElementById('formSuccess');
        const failureMessage = document.getElementById('formFailure');

        const errors = {
            name: document.getElementById('nameError'),
            email: document.getElementById('emailError'),
            message: document.getElementById('messageError')
        };

        // Validation functions
        function validateName(value) {
            if (!value || value.trim().length < 2) {
                return 'Ad ən azı 2 simvoldan ibarət olmalıdır';
            }
            return '';
        }

        function validateEmail(value) {
            if (!value) {
                return 'E-poçt ünvanı tələb olunur';
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return 'Düzgün e-poçt ünvanı daxil edin';
            }
            return '';
        }

        function validateMessage(value) {
            if (!value || value.trim().length < 10) {
                return 'Mesaj ən azı 10 simvoldan ibarət olmalıdır';
            }
            return '';
        }

        // Show error
        function showError(input, errorElement, message) {
            input.classList.add('error');
            errorElement.textContent = message;
        }

        // Clear error
        function clearError(input, errorElement) {
            input.classList.remove('error');
            errorElement.textContent = '';
        }

        // Real-time validation
        nameInput.addEventListener('blur', () => {
            const error = validateName(nameInput.value);
            if (error) {
                showError(nameInput, errors.name, error);
            } else {
                clearError(nameInput, errors.name);
            }
        });

        nameInput.addEventListener('input', () => {
            if (nameInput.classList.contains('error')) {
                const error = validateName(nameInput.value);
                if (!error) {
                    clearError(nameInput, errors.name);
                }
            }
        });

        emailInput.addEventListener('blur', () => {
            const error = validateEmail(emailInput.value);
            if (error) {
                showError(emailInput, errors.email, error);
            } else {
                clearError(emailInput, errors.email);
            }
        });

        emailInput.addEventListener('input', () => {
            if (emailInput.classList.contains('error')) {
                const error = validateEmail(emailInput.value);
                if (!error) {
                    clearError(emailInput, errors.email);
                }
            }
        });

        messageInput.addEventListener('blur', () => {
            const error = validateMessage(messageInput.value);
            if (error) {
                showError(messageInput, errors.message, error);
            } else {
                clearError(messageInput, errors.message);
            }
        });

        messageInput.addEventListener('input', () => {
            if (messageInput.classList.contains('error')) {
                const error = validateMessage(messageInput.value);
                if (!error) {
                    clearError(messageInput, errors.message);
                }
            }
        });

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Hide previous messages
            successMessage.classList.remove('show');
            failureMessage.classList.remove('show');

            // Validate all fields
            const nameError = validateName(nameInput.value);
            const emailError = validateEmail(emailInput.value);
            const messageError = validateMessage(messageInput.value);

            let hasErrors = false;

            if (nameError) {
                showError(nameInput, errors.name, nameError);
                hasErrors = true;
            }

            if (emailError) {
                showError(emailInput, errors.email, emailError);
                hasErrors = true;
            }

            if (messageError) {
                showError(messageInput, errors.message, messageError);
                hasErrors = true;
            }

            if (hasErrors) {
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            // Simulate form submission (replace with actual API call)
            try {
                await new Promise(resolve => setTimeout(resolve, 1500));

                // Success
                successMessage.classList.add('show');
                form.reset();
                clearError(nameInput, errors.name);
                clearError(emailInput, errors.email);
                clearError(messageInput, errors.message);

                // Hide success message after 5 seconds
                setTimeout(() => {
                    successMessage.classList.remove('show');
                }, 5000);

            } catch (error) {
                // Failure
                failureMessage.classList.add('show');

                // Hide failure message after 5 seconds
                setTimeout(() => {
                    failureMessage.classList.remove('show');
                }, 5000);
            } finally {
                // Reset loading state
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        });
    }

    // ===========================
    // SCROLL ANIMATIONS
    // ===========================
    function initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe fade-in elements
        const fadeElements = document.querySelectorAll('.fade-in');
        fadeElements.forEach(element => {
            observer.observe(element);
        });
    }

    // ===========================
    // PERFORMANCE OPTIMIZATION
    // ===========================

    // Debounce function for scroll events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Throttle function for frequently called events
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // ===========================
    // INITIALIZATION
    // ===========================
    function init() {
        // Wait for DOM to be fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeApp);
        } else {
            initializeApp();
        }
    }

    function initializeApp() {
        console.log('🚀 Teymur Store initialized');

        // Initialize all components
        initNavigation();
        initAccordion();
        initModal();
        initContactForm();
        initScrollAnimations();

        // Performance optimization
        console.log('✅ All components loaded successfully');
    }

    // Start the app
    init();

    // ===========================
    // EXPORT (for testing)
    // ===========================
    window.TeymurStore = {
        version: '1.0.0',
        init: initializeApp
    };

})();
