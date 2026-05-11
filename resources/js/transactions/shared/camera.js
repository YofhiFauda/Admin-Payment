/**
 * ═══════════════════════════════════════════════
 * CAMERA HANDLER — WebRTC camera integration
 * ═══════════════════════════════════════════════
 */

export class CameraHandler {
    constructor() {
        this.modal = document.getElementById('camera-modal');
        this.video = document.getElementById('camera-video');
        this.canvas = document.getElementById('camera-canvas');
        this.capturedImage = document.getElementById('captured-image');
        this.previewContainer = document.getElementById('captured-preview-container');
        this.loading = document.getElementById('camera-loading');
        this.controlsCapture = document.getElementById('controls-capture');
        this.controlsResult = document.getElementById('controls-result');
        
        this.stream = null;
        this.targetInputId = null;
        this.currentFacingMode = 'environment'; // Default to back camera
        
        this.initEventListeners();
    }

    initEventListeners() {
        if (!this.modal) return;

        document.getElementById('close-camera')?.addEventListener('click', () => this.closeCamera());
        document.getElementById('capture-photo')?.addEventListener('click', () => this.capturePhoto());
        document.getElementById('retake-photo')?.addEventListener('click', () => this.retakePhoto());
        document.getElementById('use-photo')?.addEventListener('click', () => this.usePhoto());
        document.getElementById('switch-camera')?.addEventListener('click', () => this.switchCamera());

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.closeCamera();
            }
        });
    }

    async openCamera(targetInputId) {
        this.targetInputId = targetInputId;
        
        // Show modal with animation
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        
        // Reset UI
        this.retakePhoto();
        this.loading.classList.remove('hidden');
        
        setTimeout(() => {
            this.modal.classList.add('opacity-100');
        }, 10);

        try {
            await this.startStream();
            this.loading.classList.add('hidden');
            if (typeof window.lucide !== 'undefined') window.lucide.createIcons({ root: this.modal });
        } catch (error) {
            console.error('Camera Error:', error);
            this.showError('Gagal mengakses kamera. Pastikan izin kamera telah diberikan.');
            this.closeCamera();
        }
    }

    async startStream() {
        if (this.stream) {
            this.stopStream();
        }

        const constraints = {
            video: {
                facingMode: this.currentFacingMode,
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: false
        };

        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
        this.video.srcObject = this.stream;
    }

    stopStream() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        this.video.srcObject = null;
    }

    closeCamera() {
        this.stopStream();
        this.modal.classList.remove('opacity-100');
        setTimeout(() => {
            this.modal.classList.add('hidden');
            this.modal.classList.remove('flex');
        }, 300);
    }

    capturePhoto() {
        const width = this.video.videoWidth;
        const height = this.video.videoHeight;
        
        this.canvas.width = width;
        this.canvas.height = height;
        
        const context = this.canvas.getContext('2d');
        
        // If front camera, we might want to mirror it, but usually standard capture is better
        context.drawImage(this.video, 0, 0, width, height);
        
        const dataUrl = this.canvas.toDataURL('image/jpeg', 0.9);
        this.capturedImage.src = dataUrl;
        
        // Show preview
        this.previewContainer.classList.remove('hidden');
        this.controlsCapture.classList.add('hidden');
        this.controlsResult.classList.remove('hidden');
    }

    retakePhoto() {
        this.previewContainer.classList.add('hidden');
        this.controlsCapture.classList.remove('hidden');
        this.controlsResult.classList.add('hidden');
        this.capturedImage.src = '';
    }

    usePhoto() {
        if (!this.targetInputId) return;

        this.canvas.toBlob((blob) => {
            const file = new File([blob], `camera-capture-${Date.now()}.jpg`, { type: 'image/jpeg' });
            
            const input = document.getElementById(this.targetInputId);
            if (input) {
                // Use DataTransfer to programmatically set files on input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
                
                // Trigger change event so the uploader logic picks it up
                input.dispatchEvent(new Event('change', { bubbles: true }));
                
                if (window.showToast) window.showToast('Foto berhasil diambil', 'success');
                this.closeCamera();
            }
        }, 'image/jpeg', 0.9);
    }

    async switchCamera() {
        this.currentFacingMode = this.currentFacingMode === 'user' ? 'environment' : 'user';
        this.loading.classList.remove('hidden');
        try {
            await this.startStream();
        } catch (error) {
            console.error('Switch Camera Error:', error);
        } finally {
            this.loading.classList.add('hidden');
        }
    }

    showError(message) {
        if (window.showToast) {
            window.showToast(message, 'error');
        } else {
            alert(message);
        }
    }
}
