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

        const echoChannel = window.Echo.private(channelName);

        // Listen to transaction.created event
        echoChannel.listen('.transaction.created', (e) => {
            console.log('🆕 [REALTIME] Transaction Created:', e);
            if (isIndexPage()) {
                SearchEngine.refresh();
            }
        });

        // Listen to transaction.updated event
        echoChannel.listen('.transaction.updated', (e) => {
            console.log('🔄 [REALTIME] Transaction Updated:', e);
            if (isIndexPage()) {
                SearchEngine.refresh();
            }
        });

        // Listen to transaction.deleted event
        echoChannel.listen('.transaction.deleted', (e) => {
            console.log('🗑️ [REALTIME] Transaction Deleted:', e);
            if (isIndexPage()) {
                SearchEngine.refresh();
            }
        });

        console.log(`📡 [REALTIME] Echo listener initialized on channel: ${channelName}`);
    }
}

