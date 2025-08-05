/**
 * Enhanced Main JavaScript File
 * مختبرات التضامن الدولية - Enhanced Version
 * Author: Enhanced by AI Assistant
 * Version: 2.0
 */

(function() {
  "use strict";

  /**
   * Enhanced Utility Functions
   */
  const Utils = {
    // Debounce function for performance optimization
    debounce: function(func, wait, immediate) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          timeout = null;
          if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
      };
    },

    // Throttle function for scroll events
    throttle: function(func, limit) {
      let inThrottle;
      return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
          func.apply(context, args);
          inThrottle = true;
          setTimeout(() => (inThrottle = false), limit);
        }
      };
    },

    // Check if element is in viewport
    isInViewport: function(element) {
      const rect = element.getBoundingClientRect();
      return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <=
          (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <=
          (window.innerWidth || document.documentElement.clientWidth)
      );
    },

    // Smooth scroll to element
    scrollToElement: function(element, offset = 0) {
      const elementPosition = element.offsetTop - offset;
      window.scrollTo({
        top: elementPosition,
        behavior: "smooth"
      });
    }
  };

  /**
   * Enhanced Header Management
   */
  const HeaderManager = {
    init: function() {
      this.header = document.querySelector("#header");
      this.navbar = document.querySelector("#navbar");
      this.mobileNavShow = document.querySelector(".mobile-nav-show");
      this.mobileNavHide = document.querySelector(".mobile-nav-hide");

      if (this.header) {
        this.bindEvents();
        this.initMobileNav();
        this.initScrollSpy();
      }
    },

    bindEvents: function() {
      // Enhanced scroll effect with throttling
      window.addEventListener(
        "scroll",
        Utils.throttle(() => {
          this.handleScroll();
        }, 10)
      );

      // Handle window resize
      window.addEventListener(
        "resize",
        Utils.debounce(() => {
          this.handleResize();
        }, 250)
      );
    },

    handleScroll: function() {
      const scrollTop =
        window.pageYOffset || document.documentElement.scrollTop;

      // Header sticky effect
      if (scrollTop > 100) {
        this.header.classList.add("sticked");
      } else {
        this.header.classList.remove("sticked");
      }

      // Update scroll progress
      this.updateScrollProgress();
    },

    updateScrollProgress: function() {
      const scrollProgress = document.getElementById("scrollProgress");
      if (scrollProgress) {
        const scrollTop = window.pageYOffset;
        const docHeight = document.body.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        scrollProgress.style.width = scrollPercent + "%";
      }
    },

    handleResize: function() {
      // Close mobile nav on resize to desktop
      if (window.innerWidth > 1279) {
        document.body.classList.remove("mobile-nav-active");
      }
    },

    initMobileNav: function() {
      // Close mobile nav when clicking on links
      const navLinks = document.querySelectorAll("#navbar a");
      navLinks.forEach(link => {
        link.addEventListener("click", () => {
          document.body.classList.remove("mobile-nav-active");
        });
      });

      // Close mobile nav when clicking outside
      document.addEventListener("click", e => {
        if (
          !this.navbar.contains(e.target) &&
          !this.mobileNavShow.contains(e.target)
        ) {
          document.body.classList.remove("mobile-nav-active");
        }
      });
    },

    initScrollSpy: function() {
      const sections = document.querySelectorAll("section[id]");
      const navLinks = document.querySelectorAll('#navbar a[href^="#"]');

      if (sections.length === 0 || navLinks.length === 0) return;

      window.addEventListener(
        "scroll",
        Utils.throttle(() => {
          let current = "";

          sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (window.pageYOffset >= sectionTop - 200) {
              current = section.getAttribute("id");
            }
          });

          navLinks.forEach(link => {
            link.classList.remove("active");
            if (link.getAttribute("href") === "#" + current) {
              link.classList.add("active");
            }
          });
        }, 10)
      );
    }
  };

  /**
   * Enhanced Smooth Scrolling
   */
  const SmoothScroll = {
    init: function() {
      this.bindEvents();
    },

    bindEvents: function() {
      // Handle all anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", e => {
          e.preventDefault();
          const target = document.querySelector(anchor.getAttribute("href"));
          if (target) {
            const headerHeight = document.querySelector("#header").offsetHeight;
            Utils.scrollToElement(target, headerHeight + 20);
          }
        });
      });

      // Scroll to top button
      const scrollTop = document.querySelector(".scroll-top");
      if (scrollTop) {
        window.addEventListener(
          "scroll",
          Utils.throttle(() => {
            if (window.scrollY > 100) {
              scrollTop.classList.add("active");
            } else {
              scrollTop.classList.remove("active");
            }
          }, 10)
        );

        scrollTop.addEventListener("click", e => {
          e.preventDefault();
          window.scrollTo({
            top: 0,
            behavior: "smooth"
          });
        });
      }
    }
  };

  /**
   * Enhanced Loading Manager
   */
  const LoadingManager = {
    init: function() {
      this.loadingOverlay = document.getElementById("loadingOverlay");
      this.handlePageLoad();
    },

    handlePageLoad: function() {
      window.addEventListener("load", () => {
        if (this.loadingOverlay) {
          this.loadingOverlay.style.opacity = "1";
          setTimeout(() => {
            this.loadingOverlay.style.display = "none";
          }, 300);
        }
      });
    }
  };

  /**
   * Enhanced Form Handler
   */
  const FormHandler = {
    init: function() {
      this.forms = document.querySelectorAll(".php-email-form");
      this.bindEvents();
    },

    bindEvents: function() {
      this.forms.forEach(form => {
        form.addEventListener("submit", e => {
          this.handleSubmit(e, form);
        });

        // Real-time validation
        const inputs = form.querySelectorAll("input, textarea");
        inputs.forEach(input => {
          input.addEventListener("blur", () => {
            this.validateField(input);
          });
        });
      });
    },

    handleSubmit: function(e, form) {
      e.preventDefault();

      if (!this.validateForm(form)) {
        return;
      }

      this.showLoading(form);

      // Simulate form submission (replace with actual AJAX call)
      setTimeout(() => {
        this.showSuccess(form);
      }, 1000);
    },

    validateForm: function(form) {
      let isValid = true;
      const inputs = form.querySelectorAll(
        "input[required], textarea[required]"
      );

      inputs.forEach(input => {
        if (!this.validateField(input)) {
          isValid = false;
        }
      });

      return isValid;
    },

    validateField: function(field) {
      const value = field.value.trim();
      let isValid = true;

      // Remove existing error messages
      this.removeError(field);

      if (field.hasAttribute("required") && !value) {
        this.showError(field, "هذا الحقل مطلوب");
        isValid = false;
      } else if (field.type === "email" && value && !this.isValidEmail(value)) {
        this.showError(field, "يرجى إدخال بريد إلكتروني صحيح");
        isValid = false;
      }

      return isValid;
    },

    isValidEmail: function(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    showError: function(field, message) {
      field.classList.add("is-invalid");
      const errorDiv = document.createElement("div");
      errorDiv.className = "invalid-feedback";
      errorDiv.textContent = message;
      field.parentNode.appendChild(errorDiv);
    },

    removeError: function(field) {
      field.classList.remove("is-invalid");
      const errorDiv = field.parentNode.querySelector(".invalid-feedback");
      if (errorDiv) {
        errorDiv.remove();
      }
    },

    showLoading: function(form) {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.innerHTML =
          '<i class="bi bi-hourglass-split me-2"></i>جاري الإرسال...';
        submitBtn.disabled = true;
      }
    },

    showSuccess: function(form) {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.innerHTML =
          '<i class="bi bi-check-circle me-2"></i>تم الإرسال بنجاح';
        submitBtn.classList.remove("btn-primary");
        submitBtn.classList.add("btn-success");

        setTimeout(() => {
          submitBtn.innerHTML = "إرسال الرسالة";
          submitBtn.disabled = false;
          submitBtn.classList.remove("btn-success");
          submitBtn.classList.add("btn-primary");
          form.reset();
        }, 3000);
      }
    }
  };

  /**
   * Enhanced Animation Manager
   */
  const AnimationManager = {
    init: function() {
      this.initCounters();
      this.initParallax();
      this.initHoverEffects();
    },

    initCounters: function() {
      const counters = document.querySelectorAll(".purecounter");

      const observerOptions = {
        threshold: 0.5,
        rootMargin: "0px 0px -100px 0px"
      };

      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      counters.forEach(counter => {
        observer.observe(counter);
      });
    },

    animateCounter: function(counter) {
      const target = parseInt(counter.getAttribute("data-purecounter-end"));
      const duration =
        parseInt(counter.getAttribute("data-purecounter-duration")) * 1000;
      const start =
        parseInt(counter.getAttribute("data-purecounter-start")) || 0;

      let current = start;
      const increment = target / (duration / 16);

      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        counter.textContent = Math.floor(current);
      }, 16);
    },

    initParallax: function() {
      const parallaxElements = document.querySelectorAll("[data-parallax]");

      if (parallaxElements.length > 0) {
        window.addEventListener(
          "scroll",
          Utils.throttle(() => {
            const scrollTop = window.pageYOffset;

            parallaxElements.forEach(element => {
              const speed = element.getAttribute("data-parallax") || 0.5;
              const yPos = -(scrollTop * speed);
              element.style.transform = `translateY(${yPos}px)`;
            });
          }, 10)
        );
      }
    },

    initHoverEffects: function() {
      // Enhanced card hover effects
      const cards = document.querySelectorAll(
        ".service-card, .news-card, .team-card, .gallery-item"
      );

      cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
          card.style.transform = "translateY(-10px) scale(1.02)";
        });

        card.addEventListener("mouseleave", () => {
          card.style.transform = "translateY(0) scale(1)";
        });
      });
    }
  };

  /**
   * Enhanced Gallery Manager
   */
  const GalleryManager = {
    init: function() {
      this.initLightbox();
      this.initImageLazyLoading();
    },

    initLightbox: function() {
      // Initialize GLightbox if available
      if (typeof GLightbox !== "undefined") {
        const lightbox = GLightbox({
          selector: ".gallery-lightbox",
          touchNavigation: true,
          loop: true,
          autoplayVideos: true
        });
      }
    },

    initImageLazyLoading: function() {
      const images = document.querySelectorAll("img[data-src]");

      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove("lazy");
            imageObserver.unobserve(img);
          }
        });
      });

      images.forEach(img => imageObserver.observe(img));
    }
  };

  /**
   * Enhanced Search Functionality
   */
  const SearchManager = {
    init: function() {
      this.searchInput = document.querySelector("#searchInput");
      this.searchResults = document.querySelector("#searchResults");

      if (this.searchInput) {
        this.bindEvents();
      }
    },

    bindEvents: function() {
      this.searchInput.addEventListener(
        "input",
        Utils.debounce(e => {
          this.handleSearch(e.target.value);
        }, 300)
      );
    },

    handleSearch: function(query) {
      if (query.length < 2) {
        this.hideResults();
        return;
      }

      // Simulate search (replace with actual search logic)
      const results = this.performSearch(query);
      this.displayResults(results);
    },

    performSearch: function(query) {
      // Mock search results
      const mockData = [
        { title: "فحوصات ما قبل الزواج", url: "#services", type: "خدمة" },
        { title: "الأمراض المعدية", url: "#services", type: "خدمة" },
        { title: "فرع النشمة", url: "news.html#branches", type: "فرع" },
        { title: "أحمد القدسي", url: "#team", type: "طبيب" }
      ];

      return mockData.filter(item =>
        item.title.toLowerCase().includes(query.toLowerCase())
      );
    },

    displayResults: function(results) {
      if (!this.searchResults) return;

      if (results.length === 0) {
        this.searchResults.innerHTML =
          '<div class="no-results">لا توجد نتائج</div>';
      } else {
        const resultsHTML = results
          .map(
            result => `
          <div class="search-result-item">
            <a href="${result.url}">
              <div class="result-title">${result.title}</div>
              <div class="result-type">${result.type}</div>
            </a>
          </div>
        `
          )
          .join("");

        this.searchResults.innerHTML = resultsHTML;
      }

      this.showResults();
    },

    showResults: function() {
      if (this.searchResults) {
        this.searchResults.style.display = "block";
      }
    },

    hideResults: function() {
      if (this.searchResults) {
        this.searchResults.style.display = "none";
      }
    }
  };

  /**
   * Enhanced Accessibility Features
   */
  const AccessibilityManager = {
    init: function() {
      this.initKeyboardNavigation();
      this.initFocusManagement();
      this.initARIALabels();
    },

    initKeyboardNavigation: function() {
      // Enhanced keyboard navigation for mobile menu
      document.addEventListener("keydown", e => {
        if (e.key === "Escape") {
          document.body.classList.remove("mobile-nav-active");
        }
      });
    },

    initFocusManagement: function() {
      // Trap focus in mobile menu when open
      const navbar = document.querySelector("#navbar");
      if (navbar) {
        const focusableElements = navbar.querySelectorAll(
          'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length > 0) {
          const firstElement = focusableElements[0];
          const lastElement = focusableElements[focusableElements.length - 1];

          navbar.addEventListener("keydown", e => {
            if (e.key === "Tab") {
              if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                  e.preventDefault();
                  lastElement.focus();
                }
              } else {
                if (document.activeElement === lastElement) {
                  e.preventDefault();
                  firstElement.focus();
                }
              }
            }
          });
        }
      }
    },

    initARIALabels: function() {
      // Add ARIA labels to interactive elements
      const buttons = document.querySelectorAll("button:not([aria-label])");
      buttons.forEach(button => {
        if (button.textContent.trim()) {
          button.setAttribute("aria-label", button.textContent.trim());
        }
      });
    }
  };

  /**
   * Enhanced Performance Monitor
   */
  const PerformanceMonitor = {
    init: function() {
      this.monitorPageLoad();
      this.monitorScrollPerformance();
    },

    monitorPageLoad: function() {
      window.addEventListener("load", () => {
        if ("performance" in window) {
          const loadTime =
            performance.timing.loadEventEnd -
            performance.timing.navigationStart;
          console.log(`Page load time: ${loadTime}ms`);

          // Report slow loading if needed
          if (loadTime > 3000) {
            console.warn("Page loading is slow. Consider optimization.");
          }
        }
      });
    },

    monitorScrollPerformance: function() {
      let scrollCount = 0;
      let lastScrollTime = Date.now();

      window.addEventListener("scroll", () => {
        scrollCount++;
        const currentTime = Date.now();

        if (currentTime - lastScrollTime > 1000) {
          if (scrollCount > 60) {
            console.warn(
              "High scroll frequency detected. Consider throttling."
            );
          }
          scrollCount = 0;
          lastScrollTime = currentTime;
        }
      });
    }
  };

  /**
   * Main Initialization
   */
  const App = {
    init: function() {
      // Wait for DOM to be ready
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
          this.initializeModules();
        });
      } else {
        this.initializeModules();
      }
    },

    initializeModules: function() {
      try {
        HeaderManager.init();
        SmoothScroll.init();
        LoadingManager.init();
        FormHandler.init();
        AnimationManager.init();
        GalleryManager.init();
        SearchManager.init();
        AccessibilityManager.init();
        PerformanceMonitor.init();

        console.log("Enhanced website initialized successfully");
      } catch (error) {
        console.error("Error initializing website:", error);
      }
    }
  };

  // Initialize the application
  App.init();
})();
