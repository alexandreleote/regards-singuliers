import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item']
    static values = {
        activeIndex: Number
    }

    connect() {
        this.activeIndexValue = -1;
    }

    toggle(index) {
        if (this.activeIndexValue === index) {
            this.activeIndexValue = -1;
            this.itemTargets[index].classList.remove('active');
        } else {
            if (this.activeIndexValue !== -1) {
                this.itemTargets[this.activeIndexValue].classList.remove('active');
            }
            this.activeIndexValue = index;
            this.itemTargets[index].classList.add('active');
        }
    }

    disconnect() {
        this.activeIndexValue = -1;
        this.itemTargets.forEach(item => item.classList.remove('active'));
    }
} 