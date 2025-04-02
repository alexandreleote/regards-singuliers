import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['header']
    static values = {
        lastScroll: Number,
        isScrolled: Boolean
    }

    connect() {
        this.lastScrollValue = window.scrollY;
        this.isScrolledValue = false;
        window.addEventListener('scroll', this.handleScroll.bind(this));
    }

    handleScroll() {
        const currentScroll = window.scrollY;
        
        if (currentScroll > 100) {
            this.isScrolledValue = true;
            this.headerTarget.classList.add('scrolled');
        } else {
            this.isScrolledValue = false;
            this.headerTarget.classList.remove('scrolled');
        }

        if (currentScroll > this.lastScrollValue && currentScroll > 100) {
            this.headerTarget.classList.add('hide');
        } else {
            this.headerTarget.classList.remove('hide');
        }

        this.lastScrollValue = currentScroll;
    }

    disconnect() {
        window.removeEventListener('scroll', this.handleScroll.bind(this));
    }
} 