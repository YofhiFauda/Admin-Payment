export class Uploader {
    constructor(viewerSelector) {
        this.imageViewer = document.getElementById(viewerSelector);
        this.viewerImage = document.getElementById('viewer-image');
        this.closeViewer = document.getElementById('close-viewer');
        this.refWrapper = document.getElementById('ref-photo-wrapper');
        this.lastFocusedElement = null;

        this.init();
    }

    init() {
        if (!this.imageViewer) return;

        const openViewer = (src) => {
            this.lastFocusedElement = document.activeElement;
            this.viewerImage.src = src;
            this.imageViewer.classList.remove('hidden');
            this.imageViewer.classList.add('flex');
            
            requestAnimationFrame(() => {
                this.imageViewer.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons({ root: this.imageViewer });
                }
                
                setTimeout(() => {
                    if (this.closeViewer) this.closeViewer.focus();
                }, 50);
            });
        };

        const closeViewerFn = () => {
            if (document.activeElement && this.imageViewer.contains(document.activeElement)) {
                document.activeElement.blur();
            }
            
            this.imageViewer.classList.add('hidden');
            this.imageViewer.classList.remove('flex');
            document.body.style.overflow = '';
            this.imageViewer.setAttribute('aria-hidden', 'true');
            
            setTimeout(() => { 
                this.viewerImage.src = '';
                if (this.lastFocusedElement && this.lastFocusedElement.focus) {
                    this.lastFocusedElement.focus();
                }
            }, 200);
        };

        // Klik wrapper → buka modal
        if (this.refWrapper) {
            this.refWrapper.addEventListener('click', () => {
                const img = this.refWrapper.querySelector('img');
                if (img) openViewer(img.src);
            });
        }

        // Tombol X → tutup
        if (this.closeViewer) {
            this.closeViewer.addEventListener('click', (e) => {
                e.stopPropagation();
                closeViewerFn();
            });
        }

        // Klik backdrop (di luar viewer-card) → tutup
        this.imageViewer.addEventListener('click', (e) => {
            if (e.target === this.imageViewer) closeViewerFn();
        });

        // ESC → tutup
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.imageViewer.classList.contains('hidden')) {
                closeViewerFn();
            }
        });

        // Initialize aria-hidden
        this.imageViewer.setAttribute('aria-hidden', 'true');
    }
}
