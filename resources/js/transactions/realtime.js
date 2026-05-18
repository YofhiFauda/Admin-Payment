import { SearchEngine } from './search-engine.js';
import { Config } from './config.js';

// Helper: only run SearchEngine actions when the index page is active
const isIndexPage = () => !!document.getElementById('search-results-container');

export function initRealtime() {
    if (typeof window.Echo !== 'undefined') {
        const role = Config.user.role;
        const id = Config.user.id;
        
        // Teknisi hanya subscribe ke personal channel
        // Owner, Atasan, Admin subscribe ke global channel
        const channelName = role === 'teknisi' 
            ? `transactions.${id}` 
            : 'transactions';

        // we only subscribe to the channel here to ensure the connection is active.
        // The actual event listeners are handled globally in app.blade.php
        // to avoid duplication and handle toasts across all pages.
        const echoChannel = window.Echo.private(channelName);

        // console.log(`📡 [REALTIME] Echo subscription active on channel: ${channelName}`);
    }
}
