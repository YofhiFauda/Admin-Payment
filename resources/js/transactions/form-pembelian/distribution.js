import { BranchDistribution as BaseDistribution } from '../shared/distribution.js';

/**
 * ═══════════════════════════════════════════════════════════════
 * DISTRIBUTION BRIDGE (Pembelian)
 * Connects Pembelian form to the shared distribution logic.
 * ═══════════════════════════════════════════════════════════════
 */

export class BranchDistribution extends BaseDistribution {
    constructor(totalInputSelector, submitBtnSelector, options = {}) {
        // Pembelian typically requires branch selection (not optional)
        const defaultOptions = {
            isOptional: false, 
            tolerance: 2
        };
        
        super(totalInputSelector, submitBtnSelector, { ...defaultOptions, ...options });
    }

    // You can override methods from BaseDistribution here if Pembelian needs specific behavior
}
