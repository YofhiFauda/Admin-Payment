// ────────────────────── Export Modal Logic ─────────────────────────
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
}

// doExport menerima referensi button (this) agar tidak perlu getElementById
// yang bisa gagal jika elemen belum/sudah di-unmount dari DOM.
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

    // ✅ Gunakan window.location.origin agar kompatibel dengan
    // Cloudflare tunnel, localhost, dan domain apapun.
    const url =
        window.location.origin + "/transactions/export?" + params.toString();

    // ── Set loading state ──────────────────────────────
    // Gunakan parameter `btnEl` (this) — dijamin valid karena
    // dilewatkan langsung dari event handler.
    const btn = btnEl || document.getElementById("btn-do-export");
    const btnCancel = document.getElementById("btn-cancel-export");
    const idleSpan = document.getElementById("export-btn-idle");
    const loadingSpan = document.getElementById("export-btn-loading");

    if (btn) btn.disabled = true;
    if (btnCancel) btnCancel.disabled = true;
    if (idleSpan) idleSpan.classList.add("hidden");
    if (loadingSpan) loadingSpan.classList.remove("hidden");

    // ── Fetch as blob → trigger download ───────────────
    fetch(url, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        },
        credentials: "same-origin",
    })
        .then((response) => {
            if (!response.ok)
                throw new Error("Server error " + response.status);
            const disposition =
                response.headers.get("Content-Disposition") || "";
            const match = disposition.match(/filename="?([^"]+)"?/);
            const filename = match ? match[1] : "Laporan_Transaksi.xlsx";
            return response.blob().then((blob) => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            const objectUrl = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = objectUrl;
            a.download = filename;
            a.style.display = "none";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);

            closeExportModal();
            setTimeout(() => resetExportBtn(), 300);
        })
        .catch((err) => {
            console.error("[Export] Error:", err);
            alert(
                "Gagal mengunduh laporan. Silakan coba lagi.\n" + err.message,
            );
            resetExportBtn();
        });
}

export function resetExportBtn() {
    const btn = document.getElementById("btn-do-export");
    const btnCancel = document.getElementById("btn-cancel-export");
    const idleSpan = document.getElementById("export-btn-idle");
    const loadingSpan = document.getElementById("export-btn-loading");

    if (btn) btn.disabled = false;
    if (btnCancel) btnCancel.disabled = false;
    if (idleSpan) idleSpan.classList.remove("hidden");
    if (loadingSpan) loadingSpan.classList.add("hidden");
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

