import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['gallery', 'image']
    static values = {
        currentIndex: Number,
        isFullscreen: Boolean
    }

    connect() {
        this.currentIndexValue = 0;
        this.isFullscreenValue = false;
    }

    next() {
        if (this.currentIndexValue < this.imageTargets.length - 1) {
            this.currentIndexValue++;
            this.updateGallery();
        }
    }

    previous() {
        if (this.currentIndexValue > 0) {
            this.currentIndexValue--;
            this.updateGallery();
        }
    }

    updateGallery() {
        this.imageTargets.forEach((image, index) => {
            if (index === this.currentIndexValue) {
                image.classList.add('active');
            } else {
                image.classList.remove('active');
            }
        });
    }

    toggleFullscreen() {
        this.isFullscreenValue = !this.isFullscreenValue;
        this.galleryTarget.classList.toggle('fullscreen');
        document.body.classList.toggle('gallery-fullscreen');
    }

    disconnect() {
        this.isFullscreenValue = false;
        this.galleryTarget.classList.remove('fullscreen');
        document.body.classList.remove('gallery-fullscreen');
    }
} 