const fs = require('fs');

const src = fs.readFileSync('testing_index.blade.php', 'utf8');
let dst = fs.readFileSync('resources/views/transactions/index.blade.php', 'utf8');

// The exact HTML snippet from testing_index.blade.php
const htmlStartStr = '<div id="payment-modal"';
const htmlStartInfo = src.indexOf(htmlStartStr);
let htmlEndInfo = src.indexOf('<div id="override-modal"', htmlStartInfo);
if (htmlEndInfo === -1) htmlEndInfo = src.indexOf('@endpush', htmlStartInfo);
const htmlSnippet = src.substring(htmlStartInfo, htmlEndInfo);

// The exact JS snippet from testing_index.blade.php
const jsStartStr = '    function openPaymentModal(id) {';
const jsStartInfo = src.indexOf(jsStartStr);
const jsEndStr = "bindAjaxForm('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.');";
const jsEndInfo = src.indexOf(jsEndStr, jsStartInfo) + jsEndStr.length;
const jsSnippet = src.substring(jsStartInfo, jsEndInfo);

// Function to safely remove old string block
function removeBlock(text, startSign, endSign) {
    while (true) {
        let sid = text.indexOf(startSign);
        if (sid === -1) break;
        let eid = text.indexOf(endSign, sid);
        if (eid === -1) eid = sid + startSign.length; // fallback
        else eid += endSign.length;
        text = text.substring(0, sid) + text.substring(eid);
    }
    return text;
}

// 1. Strip out ALL existing payment modal HTML
dst = removeBlock(dst, '<div id="payment-modal"', '<!-- End Payment Modal -->'); // if exists
// Alternatively just split and remove by literal search of the htmlStartStr. But since it could have been edited, regex is better, 
// wait, the snippet has ID so we can just regex matches:
dst = dst.replace(/<div\s+id="payment-modal"[\s\S]*?(?=((<div\s+id="[a-zA-Z0-9_-]+-modal")|(@endpush)))/g, '');

// 2. Strip out ALL existing openPaymentModal JS
dst = dst.replace(/function\s+openPaymentModal\s*\([\s\S]*?(?=bindAjaxForm\('payment-form')bindAjaxForm\('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.'\);/g, '');


// 3. Reinject cleanly
// 3a. Inject JS before reject form
const jsInjectPos = dst.indexOf('    // Convert reject form to AJAX');
dst = dst.substring(0, jsInjectPos) + jsSnippet + '\n\n' + dst.substring(jsInjectPos);

// 3b. Inject HTML
const htmlInjectPos = dst.indexOf("@push('modals')") + "@push('modals')".length;
dst = dst.substring(0, htmlInjectPos) + '\n\n' + htmlSnippet + '\n\n' + dst.substring(htmlInjectPos);

fs.writeFileSync('resources/views/transactions/index.blade.php', dst, 'utf8');
console.log('Cleaned and re-injected just ONE copy of Payment Modal successfully!');
