import { SearchEngine } from './search-engine.js';
import { Config } from './config.js';

// Helper: only run SearchEngine actions when the index page is active
const isIndexPage = () => !!document.getElementById('search-results-container');

export function initRealtime() {
    if (typeof window.Echo !== 'undefined') {
        const role = Config.user.role;
        const id = Config.user.id;
        
        const channelName = role === 'teknisi' 
            ? `transactions.${id}` 
            : 'transactions';

        const echoChannel = window.Echo.private(channelName);

        echoChannel.listen('.transaction.updated', (e) => {
            console.log('🔔 [REALTIME] Transaction Updated:', e);
            if (isIndexPage()) {
                SearchEngine.refresh();
            }
        });

        console.log(`📡 [REALTIME] Echo listener initialized on channel: ${channelName}`);
    }
}
