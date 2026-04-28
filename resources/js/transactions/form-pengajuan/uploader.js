import { Config } from '../config.js';

export class Uploader {
    constructor() {
        this.initImageViewer();
        this.initPhotoUploadUI();
    }

    initImageViewer() {
        this.imageViewer = document.getElementById('image-viewer');
        this.viewerImage = document.getElementById('viewer-image');
        this.viewerPdf = document.getElementById('viewer-pdf');
        this.viewerFooter = document.getElementById('viewer-footer');
        this.viewerPdfLink = document.getElementById('viewer-pdf-link');
        this.closeViewerBtn = document.getElementById('close-viewer');
        this.refWrapper = document.getElementById('ref-photo-wrapper');
        this.viewerHeaderTitle = document.getElementById('viewer-header-title');

        if (this.refWrapper) {
            this.refWrapper.addEventListener('click', () => {
                const img = this.refWrapper.querySelector('img');
                if (img) this.openViewer(img.src);
            });
        }

        if (this.closeViewerBtn) {
            this.closeViewerBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.closeViewer();
            });
        }

        if (this.imageViewer) {
            this.imageViewer.addEventListener('click', (e) => {
                if (e.target === this.imageViewer) this.closeViewer();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !this.imageViewer.classList.contains('hidden')) {
                    this.closeViewer();
                }
            });
        }
    }

    openViewer(src) {
        if (!this.imageViewer) return;
        
        const isPdf = src.toLowerCase().endsWith('.pdf') || src.startsWith('data:application/pdf');
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

        if (isPdf) {
            if (isMobile) {
                window.open(src, '_blank');
                return;
            }
            if(this.viewerImage) this.viewerImage.classList.add('hidden');
            if(this.viewerPdf) {
                this.viewerPdf.classList.remove('hidden');
                this.viewerPdf.src = src;
            }
            if(this.viewerFooter) this.viewerFooter.classList.remove('hidden');
            if(this.viewerPdfLink) this.viewerPdfLink.href = src;
            if(this.viewerHeaderTitle) this.viewerHeaderTitle.textContent = 'PREVIEW DOKUMEN PDF';
        } else {
            if(this.viewerImage) {
                this.viewerImage.classList.remove('hidden');
                this.viewerImage.src = src;
            }
            if(this.viewerPdf) this.viewerPdf.classList.add('hidden');
            if(this.viewerFooter) this.viewerFooter.classList.add('hidden');
            if(this.viewerHeaderTitle) this.viewerHeaderTitle.textContent = 'PREVIEW FOTO';
        }

        this.imageViewer.classList.remove('hidden');
        this.imageViewer.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        if (typeof window.lucide !== 'undefined') window.lucide.createIcons({ root: this.imageViewer });
    }

    closeViewer() {
        if (!this.imageViewer) return;
        this.imageViewer.classList.add('hidden');
        this.imageViewer.classList.remove('flex');
        document.body.style.overflow = '';
        setTimeout(() => {
            if(this.viewerImage) this.viewerImage.src = '';
            if(this.viewerPdf) {
                this.viewerPdf.src = '';
                this.viewerPdf.classList.add('hidden');
            }
            if(this.viewerFooter) this.viewerFooter.classList.add('hidden');
        }, 200);
    }

    initPhotoUploadUI() {
        this.photoContainer = document.getElementById('photo-upload-container');
        this.photoInput = document.getElementById('reference_photo');
        this.photoDisplay = document.getElementById('photo-name-display');
        this.photoPlaceholder = document.getElementById('photo-placeholder');
        this.previewWrapper = document.getElementById('photo-preview-wrapper');
        this.previewImg = document.getElementById('photo-preview-img');

        if (this.photoContainer && this.photoInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                this.photoContainer.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                this.photoContainer.addEventListener(eventName, () => {
                    this.photoContainer.classList.add('bg-slate-100', 'border-emerald-400');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                this.photoContainer.addEventListener(eventName, () => {
                    this.photoContainer.classList.remove('bg-slate-100', 'border-emerald-400');
                }, false);
            });

            this.photoContainer.addEventListener('drop', (e) => {
                let dt = e.dataTransfer;
                let files = dt.files;
                if (files.length) {
                    this.photoInput.files = files;
                    this.updatePhotoDisplay();
                }
            }, false);

            this.photoInput.addEventListener('change', () => this.updatePhotoDisplay());
        }

        if (this.previewWrapper) {
            this.previewWrapper.addEventListener('click', () => {
                const pdfPreview = document.getElementById('photo-preview-pdf');
                if (pdfPreview && !pdfPreview.classList.contains('hidden')) {
                    this.openViewer(pdfPreview.dataset.src);
                } else if (this.previewImg && this.previewImg.src) {
                    this.openViewer(this.previewImg.src);
                }
            });
        }
    }

    updatePhotoDisplay() {
        if (this.photoInput.files && this.photoInput.files[0]) {
            const file = this.photoInput.files[0];

            if (file.size > 5 * 1024 * 1024) {
                if (window.showToast) window.showToast('Ukuran file terlalu besar (Maks. 5MB)', 'error');
                this.photoInput.value = '';
                this.resetPhotoUI();
                return;
            }

            if(this.photoDisplay) {
                this.photoDisplay.textContent = file.name;
                this.photoDisplay.classList.remove('text-slate-700');
                this.photoDisplay.classList.add('text-emerald-600');
            }

            const isPdf = file.type === 'application/pdf';
            if (isPdf) {
                const pdfNameDisplay = document.getElementById('pdf-name-display');
                if (pdfNameDisplay) pdfNameDisplay.textContent = file.name;
            }

            const icon = this.photoContainer.querySelector('i');
            if (icon) {
                icon.classList.add('text-emerald-500');
                icon.classList.remove('text-slate-400', 'opacity-50');
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                if (isPdf) {
                    if(this.previewImg) this.previewImg.classList.add('hidden');
                    const pdfPreview = document.getElementById('photo-preview-pdf');
                    if(pdfPreview) {
                        pdfPreview.classList.remove('hidden');
                        pdfPreview.dataset.src = e.target.result;
                    }
                } else {
                    if(this.previewImg) {
                        this.previewImg.classList.remove('hidden');
                        this.previewImg.src = e.target.result;
                    }
                    const pdfPreview = document.getElementById('photo-preview-pdf');
                    if(pdfPreview) pdfPreview.classList.add('hidden');
                }
                
                if(this.previewWrapper) this.previewWrapper.classList.remove('hidden');
                if(this.photoPlaceholder) this.photoPlaceholder.classList.add('hidden');
                this.photoContainer.classList.remove('border-dashed');
                this.photoContainer.classList.add('border-solid', 'border-emerald-200');

                if (typeof window.lucide !== 'undefined') window.lucide.createIcons({ root: this.photoContainer });
            }
            reader.readAsDataURL(file);

        } else {
            this.resetPhotoUI();
        }
    }

    resetPhotoUI() {
        if(this.photoDisplay) {
            this.photoDisplay.textContent = 'Pilih Foto (Klik atau Drag)';
            this.photoDisplay.classList.add('text-slate-700');
            this.photoDisplay.classList.remove('text-emerald-600');
        }
        
        if(this.photoPlaceholder) {
            const icon = this.photoPlaceholder.querySelector('i');
            if (icon) {
                icon.classList.remove('text-emerald-500');
                icon.classList.add('text-slate-400');
            }
            this.photoPlaceholder.classList.remove('hidden');
        }

        if(this.previewWrapper) this.previewWrapper.classList.add('hidden');
        const pdfPreview = document.getElementById('photo-preview-pdf');
        if(pdfPreview) pdfPreview.classList.add('hidden');
        
        if(this.photoContainer) {
            this.photoContainer.classList.add('border-dashed');
            this.photoContainer.classList.remove('border-solid', 'border-emerald-200');
        }
        
        if(this.previewImg) this.previewImg.src = '';
    }
}
