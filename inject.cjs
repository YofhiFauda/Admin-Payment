const fs = require('fs');

const src = fs.readFileSync('testing_index.blade.php', 'utf8');
let dst = fs.readFileSync('resources/views/transactions/index.blade.php', 'utf8');

// 1. Extract JS Functions
const jsStartStr = '    function openPaymentModal(id) {';
const jsStart = src.indexOf(jsStartStr);
const jsEndStr = "bindAjaxForm('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.');";
const jsEnd = src.indexOf(jsEndStr, jsStart) + jsEndStr.length;
const jsSnippet = src.substring(jsStart, jsEnd);

// 2. Extract HTML Modal
const htmlStartStr = '<div id="payment-modal"';
const htmlStart = src.indexOf(htmlStartStr);
let htmlEnd = src.indexOf('<div id="override-modal"', htmlStart);
if (htmlEnd === -1) htmlEnd = src.indexOf('@endpush', htmlStart);
const htmlSnippet = src.substring(htmlStart, htmlEnd);

// 3. Inject JS
const jsInjectPos = dst.indexOf('    // Convert reject form to AJAX');
dst = dst.substring(0, jsInjectPos) + jsSnippet + '\n\n' + dst.substring(jsInjectPos);

// 4. Inject HTML
const htmlInjectPos = dst.indexOf("@push('modals')") + "@push('modals')".length;
dst = dst.substring(0, htmlInjectPos) + '\n\n' + htmlSnippet + '\n\n' + dst.substring(htmlInjectPos);

fs.writeFileSync('resources/views/transactions/index.blade.php', dst, 'utf8');
console.log('Injection via Node completed successfully.');
