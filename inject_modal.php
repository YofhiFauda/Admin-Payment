<?php
$src = file_get_contents('testing_index.blade.php');
$dst = file_get_contents('resources/views/transactions/index.blade.php');

// 1. Extract JS Functions
$jsStart = strpos($src, '    function openPaymentModal(id) {');
if ($jsStart === false) {
    echo "JS start not found\n";
    exit(1);
}
$jsEndStr = "bindAjaxForm('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.');";
$jsEnd = strpos($src, $jsEndStr, $jsStart);
if ($jsEnd === false) {
    echo "JS end not found\n";
    exit(1);
}
$jsEnd += strlen($jsEndStr);
$jsSnippet = substr($src, $jsStart, $jsEnd - $jsStart);

// 2. Extract HTML Modal
$htmlStart = strpos($src, '<div id="payment-modal"');
if ($htmlStart === false) {
    echo "HTML start not found\n";
    exit(1);
}
$htmlEnd = strpos($src, '<div id="override-modal"', $htmlStart);
if ($htmlEnd === false) {
    $htmlEnd = strpos($src, '@endpush', $htmlStart);
}
$htmlSnippet = substr($src, $htmlStart, $htmlEnd - $htmlStart);

// Debug
echo "Extracted JS length: " . strlen($jsSnippet) . "\n";
echo "Extracted HTML length: " . strlen($htmlSnippet) . "\n";

// 3. Inject JS into $dst
$injectJsPos = strpos($dst, '    // ─── INIT AJAX FORMS');
if ($injectJsPos === false) {
    $injectJsPos = strrpos($dst, '</script>');
}
$dst = substr_replace($dst, "\n" . $jsSnippet . "\n\n", $injectJsPos, 0);

// 4. Inject HTML into $dst
$injectHtmlPos = strpos($dst, '@push(\'modals\')');
if ($injectHtmlPos !== false) {
    $injectHtmlPos += strlen("@push('modals')");
    $dst = substr_replace($dst, "\n" . $htmlSnippet . "\n", $injectHtmlPos, 0);
} else {
    $dst .= "\n@push('modals')\n" . $htmlSnippet . "\n@endpush\n";
}

file_put_contents('resources/views/transactions/index.blade.php', $dst);
echo "Successfully injected Payment Modal and JS logic!\n";
