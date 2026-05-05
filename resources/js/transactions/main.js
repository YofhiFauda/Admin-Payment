import { SearchEngine } from "./search-engine.js";
import { initModals } from "./modals.js";
import { initPaymentHandlers } from "./payment.js";
import { initRealtime } from "./realtime.js";
import { Config } from "./config.js";
import { setAsReference, syncSearchInputs } from "./utils.js";
import "./modals-export-excel.js";
import "./form-pengajuan/index.js"; // Form Pengajuan Orchestration
import "./form-rembush/index.js"; // Form Rembush Orchestration
import "./form-pembelian/index.js"; // Form Pembelian Orchestration

document.addEventListener("DOMContentLoaded", function () {
    // Initialize Modals and Payment Handlers
    initModals();
    initPaymentHandlers();
    initRealtime();

    // Export functions globally
    window.updateFilterIndicators = updateFilterIndicators;
    window.setDateRange = setDateRange;
    window.deleteTransaction = deleteTransaction;
    window.confirmDeleteTransaction = confirmDeleteTransaction;
    window.setAsReference = setAsReference;

    // Initialize Filter Popover Toggle Logic
    initFilterPopovers();

    // Filter checkbox change event
    document
        .querySelectorAll('input[name="branch_id[]"], input[name="category[]"]')
        .forEach((cb) => {
            cb.addEventListener("change", () => {
                updateFilterIndicators();
                SearchEngine.applyFilters();
            });
        });

    // Popover search
    document.querySelectorAll(".popover-search").forEach((search) => {
        search.addEventListener("input", function () {
            const query = this.value.toLowerCase();
            const container =
                this.closest(".p-3").querySelector(".custom-scrollbar");
            if (container) {
                container.querySelectorAll("label").forEach((label) => {
                    const text = label
                        .querySelector("span")
                        .textContent.toLowerCase();
                    label.classList.toggle("hidden", !text.includes(query));
                });
            }
        });
    });

    // Quick Reset Buttons with Immediate Propagation Control
    document.addEventListener(
        "click",
        function (e) {
            // Branch Reset
            const branchReset = e.target.closest(".js-filter-branch-reset");
            if (branchReset) {
                e.preventDefault();
                e.stopImmediatePropagation();
                document
                    .querySelectorAll('input[name="branch_id[]"]')
                    .forEach((cb) => (cb.checked = false));
                updateFilterIndicators();
                SearchEngine.applyFilters();
                return;
            }

            // Category Reset
            const categoryReset = e.target.closest(".js-filter-category-reset");
            if (categoryReset) {
                e.preventDefault();
                e.stopImmediatePropagation();
                document
                    .querySelectorAll('input[name="category[]"]')
                    .forEach((cb) => (cb.checked = false));
                updateFilterIndicators();
                SearchEngine.applyFilters();
                return;
            }

            // Date Reset
            const dateReset = e.target.closest(".js-filter-date-reset");
            if (dateReset) {
                e.preventDefault();
                e.stopImmediatePropagation();
                document.getElementById("filter-start-date").value = "";
                document.getElementById("filter-end-date").value = "";
                updateFilterIndicators();
                SearchEngine.applyFilters();
                return;
            }

            // AJAX Type Filter
            const typeFilter = e.target.closest(".js-filter-type");
            if (typeFilter) {
                e.preventDefault();
                const type = typeFilter.getAttribute("data-type");
                const url = new URL(typeFilter.href);
                
                updateUrl(url);
                syncTypeUI(type);
                SearchEngine.applyFilters();
                return;
            }

            // AJAX Status Tab
            const statusTab = e.target.closest(".js-filter-status");
            if (statusTab) {
                e.preventDefault();
                const status = statusTab.getAttribute("data-status");
                const url = new URL(statusTab.href);

                updateUrl(url);
                syncStatusUI(status);
                SearchEngine.applyFilters();
                return;
            }
        },
        true,
    );

    const filterStartDate = document.getElementById("filter-start-date");
    const filterEndDate = document.getElementById("filter-end-date");
    const btnApplyDate = document.getElementById("btn-apply-date");

    function updateApplyButtonState() {
        if (!filterStartDate || !filterEndDate || !btnApplyDate) return;
        const isFilled = filterStartDate.value && filterEndDate.value;
        btnApplyDate.disabled = !isFilled;
        if (isFilled) {
            btnApplyDate.classList.remove("opacity-50", "cursor-not-allowed");
            btnApplyDate.classList.add("hover:bg-blue-700", "shadow-lg");
        } else {
            btnApplyDate.classList.add("opacity-50", "cursor-not-allowed");
            btnApplyDate.classList.remove("hover:bg-blue-700", "shadow-lg");
        }
    }

    if (filterStartDate)
        filterStartDate.addEventListener("input", updateApplyButtonState);
    if (filterEndDate)
        filterEndDate.addEventListener("input", updateApplyButtonState);
    updateApplyButtonState();

    document.querySelectorAll(".date-preset-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
            setDateRange(this.dataset.range);
        });
    });

    document.getElementById("btn-apply-date")?.addEventListener("click", () => {
        updateFilterIndicators();
        SearchEngine.applyFilters();
    });

    // Instant search with synchronization across all layouts
    const searchInputs = document.querySelectorAll(".search-input-sync");
    const searchClearBtns = document.querySelectorAll(".search-clear, #search-clear");

    searchInputs.forEach(input => {
        // Initial state for clear button
        if (input.value.trim() !== "") {
            const container = input.closest(".relative");
            container?.querySelector(".search-clear, #search-clear")?.classList.remove("hidden");
        }

        input.addEventListener("input", function () {
            const val = this.value.trim();
            
            // Sync all other search inputs
            syncSearchInputs(val);

            // Toggle clear buttons
            searchClearBtns.forEach(btn => {
                btn.classList.toggle("hidden", val === "");
            });

            // Trigger search
            SearchEngine.search(val);
        });
    });

    // Clear search functionality
    searchClearBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            syncSearchInputs("");
            searchClearBtns.forEach(b => b.classList.add("hidden"));
            SearchEngine.search("");
            
            // Refocus the nearest input
            const input = this.closest(".relative")?.querySelector(".search-input-sync");
            input?.focus();
        });
    });

    // Initialize the Search Engine
    if (document.getElementById("search-results-container")) {
        SearchEngine.init();
    }
});

function updateFilterIndicators() {
    // Branch Indicator
    const branchCheckboxes = document.querySelectorAll(
        'input[name="branch_id[]"]:checked',
    );
    const branchBtns = document.querySelectorAll(".js-filter-branch-btn");
    const branchLabels = document.querySelectorAll(".js-filter-branch-label");
    const branchResets = document.querySelectorAll(".js-filter-branch-reset");

    if (branchCheckboxes.length > 0) {
        const firstLabel = branchCheckboxes[0]
            .closest("label")
            .querySelector("span")
            .textContent.trim();
        const text =
            branchCheckboxes.length === 1
                ? firstLabel
                : `${firstLabel} +${branchCheckboxes.length - 1}`;

        branchLabels.forEach((el) => {
            el.textContent = text;
            el.classList.add("text-blue-600");
        });
        branchBtns.forEach((btn) => {
            btn.classList.add("border-blue-200", "bg-blue-50/50", "active");
        });
        branchResets.forEach((el) => el.classList.remove("hidden"));
    } else {
        branchLabels.forEach((el) => {
            el.textContent =
                window.innerWidth < 1024 ? "Pilih Cabang" : "Semua Cabang";
            el.classList.remove("text-blue-600");
        });
        branchBtns.forEach((btn) => {
            btn.classList.remove("border-blue-200", "bg-blue-50/50", "active");
        });
        branchResets.forEach((el) => el.classList.add("hidden"));
    }

    // Category Indicator
    const categoryCheckboxes = document.querySelectorAll(
        'input[name="category[]"]:checked',
    );
    const categoryBtns = document.querySelectorAll(".js-filter-category-btn");
    const categoryLabels = document.querySelectorAll(
        ".js-filter-category-label",
    );
    const categoryResets = document.querySelectorAll(
        ".js-filter-category-reset",
    );

    if (categoryCheckboxes.length > 0) {
        const firstLabel = categoryCheckboxes[0]
            .closest("label")
            .querySelector("span")
            .textContent.trim();
        const text =
            categoryCheckboxes.length === 1
                ? firstLabel
                : `${firstLabel} +${categoryCheckboxes.length - 1}`;

        categoryLabels.forEach((el) => {
            el.textContent = text;
            el.classList.add("text-blue-600");
        });
        categoryBtns.forEach((btn) => {
            btn.classList.add("border-blue-200", "bg-blue-50/50", "active");
        });
        categoryResets.forEach((el) => el.classList.remove("hidden"));
    } else {
        categoryLabels.forEach((el) => {
            el.textContent =
                window.innerWidth < 1024 ? "Pilih Kategori" : "Semua Kategori";
            el.classList.remove("text-blue-600");
        });
        categoryBtns.forEach((btn) => {
            btn.classList.remove("border-blue-200", "bg-blue-50/50", "active");
        });
        categoryResets.forEach((el) => el.classList.add("hidden"));
    }

    // Date Indicator
    const startDate = document.getElementById("filter-start-date")?.value;
    const endDate = document.getElementById("filter-end-date")?.value;
    const dateBtns = document.querySelectorAll(".js-filter-date-btn");
    const dateLabels = document.querySelectorAll(".js-filter-date-label");
    const dateResets = document.querySelectorAll(".js-filter-date-reset");

    if (startDate || endDate) {
        const formatDateStr = (dateString) => {
            if (!dateString) return "";
            const options = { day: "numeric", month: "short", year: "numeric" };
            return new Date(dateString + "T00:00:00Z").toLocaleDateString(
                "id-ID",
                options,
            );
        };

        const formattedStart = formatDateStr(startDate);
        const formattedEnd = formatDateStr(endDate);
        const text =
            startDate && endDate
                ? `${formattedStart} — ${formattedEnd}`
                : formattedStart || formattedEnd;

        dateLabels.forEach((el) => {
            el.textContent = text;
            el.classList.add("text-blue-600");
        });
        dateBtns.forEach((btn) => {
            btn.classList.add("border-blue-200", "bg-blue-50/50", "active");
        });
        dateResets.forEach((el) => el.classList.remove("hidden"));
    } else {
        dateLabels.forEach((el) => {
            el.textContent = "Pilih Tanggal";
            el.classList.remove("text-blue-600");
        });
        dateBtns.forEach((btn) => {
            btn.classList.remove("border-blue-200", "bg-blue-50/50", "active");
        });
        dateResets.forEach((el) => el.classList.add("hidden"));
    }

    if (typeof lucide !== "undefined") lucide.createIcons();
}

function setDateRange(range) {
    const start = document.getElementById("filter-start-date");
    const end = document.getElementById("filter-end-date");
    const today = new Date();
    let startDate,
        endDate = today;

    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    };

    switch (range) {
        case "today":
            startDate = today;
            break;
        case "yesterday":
            startDate = new Date();
            startDate.setDate(today.getDate() - 1);
            endDate = new Date(startDate);
            break;
        case "last7":
            startDate = new Date();
            startDate.setDate(today.getDate() - 6);
            break;
        case "last30":
            startDate = new Date();
            startDate.setDate(today.getDate() - 29);
            break;
        case "thisMonth":
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
        case "lastMonth":
            // Tanggal 1 bulan lalu
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            // Hari terakhir bulan lalu (parameter hari ke-0 otomatis mundur ke hari terakhir bulan sebelumnya)
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
    }

    if (start && end) {
        start.value = formatDate(startDate);
        end.value = formatDate(endDate);

        // Highlight active preset
        document.querySelectorAll(".date-preset-btn").forEach((p) => {
            const isActive = p.dataset.range === range;
            p.classList.toggle("bg-white", isActive);
            p.classList.toggle("text-blue-600", isActive);
            p.classList.toggle("ring-1", isActive);
            p.classList.toggle("ring-blue-100", isActive);
        });

        // Trigger input event manually so updateApplyButtonState works if we extracted it, but actually we can just manually call the logic
        const btnApplyDate = document.getElementById("btn-apply-date");
        if (btnApplyDate) {
            btnApplyDate.disabled = false;
            btnApplyDate.classList.remove("opacity-50", "cursor-not-allowed");
            btnApplyDate.classList.add("hover:bg-blue-700", "shadow-lg");
        }
    }
}

async function deleteTransaction(id) {
    if (!id) {
        console.error("Delete error: No transaction ID provided");
        showToast("ID Transaksi tidak valid.", "error");
        return;
    }

    try {
        if (typeof NProgress !== "undefined") NProgress.start();

        const response = await fetch(Config.endpoints.transactions.delete(id), {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": Config.csrfToken,
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        const result = await response.json();

        if (result.success) {
            if (typeof SearchEngine !== "undefined" && SearchEngine.refresh) {
                await SearchEngine.refresh();
            } else {
                window.location.reload();
            }

            showToast(result.message, "success");
        } else {
            showToast(result.message || "Gagal menghapus transaksi", "error");
        }
    } catch (error) {
        console.error("Delete error:", error);
        showToast("Terjadi kesalahan saat menghapus transaksi.", "error");
    } finally {
        if (typeof NProgress !== "undefined") NProgress.done();
    }
}

function confirmDeleteTransaction(id, invoiceNumber) {
    if (typeof openConfirmModal === "function") {
        openConfirmModal("deleteTransactionModal", {
            title: "Hapus Transaksi",
            message: `Apakah Anda yakin ingin menghapus transaksi <strong>${invoiceNumber}</strong> secara permanen? <br><br> <span class="text-xs text-rose-500 font-bold uppercase tracking-wider">Tindakan ini tidak dapat dibatalkan.</span>`,
            submitText: "Ya, Hapus",
            submitColor: "bg-rose-600 hover:bg-rose-700",
            icon: "trash-2",
            onConfirm: async () => {
                await deleteTransaction(id);
            },
        });
    } else {
        // Fallback to browser confirm if modal component fails
        if (
            confirm(
                `Apakah Anda yakin ingin menghapus transaksi ${invoiceNumber}?`,
            )
        ) {
            deleteTransaction(id);
        }
    }
}

/**
 * Initialize Filter Popover Toggle Logic
 * Handles opening/closing of filter dropdown menus
 */
function initFilterPopovers() {
    let activePopover = null;

    // --- TAMBAHKAN KODE INI ---
    // Pindahkan popover ke body agar kalkulasi posisi absolut tidak terganggu oleh container parent
    const popoverContainer = document.getElementById("popover-container");
    if (popoverContainer && popoverContainer.parentNode !== document.body) {
        document.body.appendChild(popoverContainer);
    }
    // -------------------------

    // Handle filter trigger button clicks
    document.addEventListener("click", function (e) {
        const trigger = e.target.closest(".filter-trigger");

        if (trigger) {
            e.preventDefault();
            e.stopPropagation();

            const targetId = trigger.getAttribute("data-target");
            const popover = document.getElementById(targetId);

            if (!popover) return;

            // If clicking the same trigger, toggle it
            if (activePopover === popover) {
                closePopover(popover, trigger);
                activePopover = null;
            } else {
                // Close any open popover first
                if (activePopover) {
                    const activeTrigger = document.querySelector(
                        `.filter-trigger[data-target="${activePopover.id}"]`,
                    );
                    closePopover(activePopover, activeTrigger);
                }

                // Open the new popover
                openPopover(popover, trigger);
                activePopover = popover;
            }
        } else if (activePopover && !e.target.closest(".filter-popover")) {
            // Click outside - close active popover
            const activeTrigger = document.querySelector(
                `.filter-trigger[data-target="${activePopover.id}"]`,
            );
            closePopover(activePopover, activeTrigger);
            activePopover = null;
        }
    });

    // Close popover when clicking inside on a checkbox (optional - keeps it open)
    document.querySelectorAll(".filter-popover").forEach((popover) => {
        popover.addEventListener("click", function (e) {
            e.stopPropagation();
        });
    });

    // Cancel and Apply buttons for date filter
    document
        .getElementById("btn-cancel-date")
        ?.addEventListener("click", () => {
            const datePopover = document.getElementById("menu-filter-date");
            const dateTrigger = document.querySelector(
                '.filter-trigger[data-target="menu-filter-date"]',
            );
            if (datePopover && dateTrigger) {
                closePopover(datePopover, dateTrigger);
                activePopover = null;
            }
        });
}

function openPopover(popover, trigger) {
    if (!popover || !trigger) return;

    // Position the popover below the trigger
    const rect = trigger.getBoundingClientRect();
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        // Mobile: full width at bottom
        popover.style.left = "16px";
        popover.style.right = "16px";
        popover.style.top = `${rect.bottom + window.scrollY + 8}px`;
        popover.style.width = "calc(100% - 32px)";
    } else {
        // Desktop: positioned below trigger
        popover.style.left = `${rect.left + window.scrollX}px`;
        popover.style.top = `${rect.bottom + window.scrollY + 8}px`;
        popover.style.width = "";
    }

    popover.classList.remove("hidden");
    trigger.classList.add("active");
}

function closePopover(popover, trigger) {
    if (!popover) return;
    popover.classList.add("hidden");
    if (trigger) trigger.classList.remove("active");
}

/**
 * Update the browser URL without refreshing the page
 */
function updateUrl(url) {
    window.history.pushState({ path: url.href }, '', url.href);
}

/**
 * Synchronize the Type Filter UI classes
 */
function syncTypeUI(activeType) {
    document.querySelectorAll('.js-filter-type').forEach(btn => {
        const type = btn.getAttribute('data-type');
        const isActive = type === activeType;
        
        // Remove all possible active/inactive classes
        btn.classList.remove('bg-slate-800', 'text-white', 'border-slate-800', 'bg-indigo-600', 'bg-teal-600', 'bg-amber-600', 'shadow-lg');
        btn.classList.add('bg-white', 'text-slate-500', 'border-slate-200');

        if (isActive) {
            btn.classList.remove('bg-white', 'text-slate-500', 'border-slate-200');
            
            if (type === 'all') btn.classList.add('bg-slate-800', 'text-white', 'border-slate-800');
            else if (type === 'rembush') btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
            else if (type === 'pengajuan') btn.classList.add('bg-teal-600', 'text-white', 'border-teal-600');
            else if (type === 'gudang') btn.classList.add('bg-amber-600', 'text-white', 'border-amber-600');
            
            // Add shadow if it's the tablet/mobile version (which uses shadow-lg)
            if (btn.closest('.hide-on-laptop, .md\\:hidden')) {
                btn.classList.add('shadow-lg');
            }
        }
    });
}

/**
 * Synchronize the Status Tabs UI classes
 */
function syncStatusUI(activeStatus) {
    document.querySelectorAll('.js-filter-status').forEach(tab => {
        const status = tab.getAttribute('data-status');
        const isActive = status === activeStatus;
        const indicator = tab.querySelector('.js-active-indicator');

        if (isActive) {
            tab.classList.remove('text-gray-500', 'hover:text-gray-700');
            tab.classList.add('text-blue-600');
            indicator?.classList.remove('hidden');
        } else {
            tab.classList.remove('text-blue-600');
            tab.classList.add('text-gray-500', 'hover:text-gray-700');
            indicator?.classList.add('hidden');
        }
    });
}
