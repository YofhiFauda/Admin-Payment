// ────────────────────── Export Modal Logic ─────────────────────────
// LAYER 2: Async Export dengan Progress Tracking + Reverb Broadcasting

let currentExportId = null;
let exportChannel = null;
let pollInterval = null;

export function openExportModal() {
    const modal = document.getElementById("export-modal");
    // Pre-fill: bulan saat ini
    const now = new Date();
    document.getElementById("export-month").value = now.getMonth() + 1;

    modal.classList.remove("hidden");
    requestAnimationFrame(() => {
        modal.classList.remove("opacity-0");
        document
            .getElementById("export-modal-card")
            .classList.remove("scale-95");
    });

    // reinit lucide icons for newly shown modal
    if (typeof lucide !== "undefined") lucide.createIcons();
}

export function closeExportModal() {
    const modal = document.getElementById("export-modal");
    modal.classList.add("opacity-0");
    document.getElementById("export-modal-card").classList.add("scale-95");
    setTimeout(() => modal.classList.add("hidden"), 200);
    
    // Cleanup
    cleanupExportListeners();
}

/**
 * doExport — Dispatch async export job + listen progress via Reverb
 */
export function doExport(btnEl) {
    const month = document.getElementById("export-month")?.value ?? "";
    const year = document.getElementById("export-year")?.value ?? "";
    const status = document.getElementById("export-status")?.value ?? "";
    const branch = document.getElementById("export-branch")?.value ?? "";
    const typeEl = document.querySelector('input[name="export_type"]:checked');
    const type = typeEl ? typeEl.value : "";

    const params = new URLSearchParams();
    if (month) params.set("month", month);
    if (year) params.set("year", year);
    if (type) params.set("type", type);
    if (status) params.set("status", status);
    if (branch) params.set("branch_id", branch);

    const url = window.location.origin + "/transactions/export/queue";

    // ── Set loading state ──────────────────────────────
    const btn = btnEl || document.getElementById("btn-do-export");
    const btnCancel = document.getElementById("btn-cancel-export");
    const idleSpan = document.getElementById("export-btn-idle");
    const loadingSpan = document.getElementById("export-btn-loading");
    const progressContainer = document.getElementById("export-progress-container");
    const progressBar = document.getElementById("export-progress-bar");
    const progressText = document.getElementById("export-progress-text");

    if (btn) btn.disabled = true;
    if (btnCancel) btnCancel.disabled = true;
    if (idleSpan) idleSpan.classList.add("hidden");
    if (loadingSpan) loadingSpan.classList.remove("hidden");

    // ── Dispatch async export ───────────────────────────
    fetch(url, {
        method: "POST",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content"),
        },
        credentials: "same-origin",
        body: JSON.stringify(Object.fromEntries(params)),
    })
        .then(async (response) => {
            // Selalu coba parse body sebagai JSON untuk dapat info error.
            const ctype = response.headers.get("content-type") || "";
            let data = null;
            if (ctype.includes("application/json")) {
                try {
                    data = await response.json();
                } catch (e) {
                    /* ignore */
                }
            }

            if (!response.ok) {
                // ── Fallback: jika async gagal (503/500), coba sync export ──
                if (response.status === 503 && data?.fallback_url) {
                    console.warn("[Export] Async unavailable, fallback to sync:", data.fallback_url);
                    window.location.href = data.fallback_url;
                    return null;
                }

                const msg = data?.message || data?.error || `HTTP ${response.status}`;
                const errorsObj = data?.errors;
                let detail = "";
                if (errorsObj && typeof errorsObj === "object") {
                    detail = "\n" + Object.entries(errorsObj)
                        .map(([k, v]) => `• ${k}: ${Array.isArray(v) ? v.join(", ") : v}`)
                        .join("\n");
                }
                throw new Error(msg + detail);
            }

            return data;
        })
        .then((data) => {
            if (!data) return; // sync fallback redirect already happened

            currentExportId = data.export_id;

            // Show progress UI
            if (progressContainer) progressContainer.classList.remove("hidden");
            if (progressText) progressText.textContent = "Memproses... 0%";
            if (progressBar) progressBar.style.width = "0%";

            // Listen via Reverb
            listenExportProgress();

            // Fallback polling jika Reverb down
            startPolling();
        })
        .catch((err) => {
            console.error("[Export] Error:", err);
            alert("Gagal memulai export.\n\n" + err.message);
            resetExportBtn();
        });
}

/**
 * Listen export progress via Reverb (real-time)
 */
function listenExportProgress() {
    if (typeof window.Echo === "undefined") {
        console.warn("[Export] Echo not available, using polling fallback");
        return;
    }

    const userId = window.userId || document.querySelector('meta[name="user-id"]')?.getAttribute("content");
    if (!userId) {
        console.warn("[Export] User ID not found, cannot subscribe to Reverb");
        return;
    }

    exportChannel = window.Echo.private(`exports.${userId}`);
    
    exportChannel.listen(".export.updated", (e) => {
        console.log("[Export] Reverb update:", e);
        
        if (e.export_id !== currentExportId) return;
        
        handleExportUpdate(e);
    });
}

/**
 * Fallback polling (jika Reverb down atau tidak tersedia)
 */
function startPolling() {
    if (pollInterval) clearInterval(pollInterval);
    
    pollInterval = setInterval(() => {
        if (!currentExportId) {
            clearInterval(pollInterval);
            return;
        }
        
        fetch(`/transactions/export/status/${currentExportId}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        })
            .then(res => res.json())
            .then(data => handleExportUpdate(data))
            .catch(err => console.error("[Export] Poll error:", err));
    }, 2000); // Poll setiap 2 detik
}

/**
 * Handle export status update (dari Reverb atau polling)
 */
function handleExportUpdate(data) {
    const progressBar = document.getElementById("export-progress-bar");
    const progressText = document.getElementById("export-progress-text");
    
    if (data.status === "processing") {
        const percent = data.progress_percent || 0;
        if (progressBar) progressBar.style.width = `${percent}%`;
        if (progressText) {
            const processed = data.processed || 0;
            const total = data.total || 0;
            progressText.textContent = `Memproses... ${percent}% (${processed}/${total} transaksi)`;
        }
    } else if (data.status === "completed") {
        if (progressBar) progressBar.style.width = "100%";
        if (progressText) progressText.textContent = "Selesai! Mengunduh file...";
        
        // Trigger download
        setTimeout(() => {
            if (data.download_url) {
                window.location.href = data.download_url;
            }
            closeExportModal();
            setTimeout(() => resetExportBtn(), 300);
        }, 500);
        
        cleanupExportListeners();
    } else if (data.status === "failed") {
        alert("Export gagal: " + (data.error_message || "Unknown error"));
        resetExportBtn();
        cleanupExportListeners();
    }
}

/**
 * Cleanup listeners & polling
 */
function cleanupExportListeners() {
    if (exportChannel) {
        exportChannel.stopListening(".export.updated");
        exportChannel = null;
    }
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    currentExportId = null;
}

export function resetExportBtn() {
    const btn = document.getElementById("btn-do-export");
    const btnCancel = document.getElementById("btn-cancel-export");
    const idleSpan = document.getElementById("export-btn-idle");
    const loadingSpan = document.getElementById("export-btn-loading");
    const progressContainer = document.getElementById("export-progress-container");

    if (btn) btn.disabled = false;
    if (btnCancel) btnCancel.disabled = false;
    if (idleSpan) idleSpan.classList.remove("hidden");
    if (loadingSpan) loadingSpan.classList.add("hidden");
    if (progressContainer) progressContainer.classList.add("hidden");
}

// ── Export Type Radio Styling ─────────────────────────
document.addEventListener("DOMContentLoaded", function () {
    const typeColors = {
        "": {
            checked: "border-slate-800 bg-slate-800 text-slate-800",
            unchecked: "border-slate-200 bg-white text-slate-500",
        },
        rembush: {
            checked: "border-indigo-600 bg-indigo-600 text-indigo-600",
            unchecked: "border-slate-200 bg-white text-slate-500",
        },
        pengajuan: {
            checked: "border-teal-600 bg-teal-600 text-teal-600",
            unchecked: "border-slate-200 bg-white text-slate-500",
        },
        gudang: {
            checked: "border-amber-600 bg-amber-600 text-amber-600",
            unchecked: "border-slate-200 bg-white text-slate-500",
        },
    };

    function updateTypeStyles() {
        document
            .querySelectorAll('input[name="export_type"]')
            .forEach((radio) => {
                const div = radio.nextElementSibling;
                const type = radio.value;
                const colors = typeColors[type] || typeColors[""];
                // Remove old
                div.className = div.className
                    .replace(
                        /(border-\w+-\d+|bg-\w+-\d+|text-\w+-\d+|text-white|text-slate-500)/g,
                        "",
                    )
                    .trim();
                div.className +=
                    " " + (radio.checked ? colors.checked : colors.unchecked);
            });
    }

    document.querySelectorAll('input[name="export_type"]').forEach((radio) => {
        radio.addEventListener("change", updateTypeStyles);
    });

    updateTypeStyles();

    // Close modal on backdrop click
    document
        .getElementById("export-modal")
        ?.addEventListener("click", function (e) {
            if (e.target === this) closeExportModal();
        });
});

// Expose functions to window for Blade onclick handlers
window.openExportModal = openExportModal;
window.closeExportModal = closeExportModal;
window.doExport = doExport;

