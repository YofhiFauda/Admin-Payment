{{-- Desktop Row Template --}}
<template id="item-row-template">
    <tr class="text-slate-600 text-xs hover:bg-slate-50/50 transition-colors" data-idx="{idx}">
        <td class="p-4 text-center text-slate-400 font-medium">{no}</td>
        <td class="p-3">
            <input type="text" name="items[{idx}][name]" value="{name}"
                placeholder="Nama item..."
                class="item-field w-full bg-transparent border-0 border-b border-slate-100
                    focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                data-field="name">
        </td>
        <td class="p-3">
            <input type="number" name="items[{idx}][qty]" value="{qty}" min="1"
                class="item-field w-full bg-transparent border-0 border-b border-slate-100
                    focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors"
                data-field="qty">
        </td>
        <td class="p-3">
            <input type="text" name="items[{idx}][unit]" value="{unit}"
                class="item-field w-full bg-transparent border-0 border-b border-slate-100
                    focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                data-field="unit">
        </td>
        <td class="p-3">
            <input type="text" value="{price_formatted}"
                class="item-price-display w-full bg-transparent border-0 border-b border-slate-100
                    focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-right">
            <input type="hidden" name="items[{idx}][price]" value="{price}" class="item-price-hidden">
        </td>
        <td class="p-4 font-bold text-slate-800 text-right">{total_formatted}</td>
        <td class="p-3">
            <input type="text" name="items[{idx}][desc]" value="{desc}"
                placeholder="Catatan..."
                class="item-field w-full bg-transparent border-0 border-b border-slate-100
                    focus:border-emerald-400 focus:ring-0 px-2 py-1 outline-none transition-colors text-slate-400"
                data-field="desc">
        </td>
        <td class="p-4 text-center">
            <button type="button" class="item-delete text-slate-300 hover:text-red-500 transition-colors">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </td>
    </tr>
</template>

{{-- Mobile Card Template --}}
<template id="item-card-template">
    <div class="p-4 space-y-4 relative bg-white" data-idx="{idx}">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">{no}</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Baris #{no}</span>
            </div>
            <button type="button" class="item-delete p-1.5 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nama Barang / Jasa</label>
                <input type="text" value="{name}"
                    placeholder="Masukkan nama barang..."
                    class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 outline-none focus:border-emerald-400 transition-all"
                    data-field="name">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Qty</label>
                    <input type="number" value="{qty}" min="1"
                        class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:border-emerald-400 transition-all"
                        data-field="qty">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Satuan</label>
                    <input type="text" value="{unit}"
                        class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-400 outline-none focus:border-emerald-400 transition-all"
                        data-field="unit">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Harga Satuan</label>
                <input type="text" value="{price_formatted}"
                    class="item-price-display w-full bg-white border-2 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-black text-emerald-600 outline-none focus:border-emerald-400 transition-all">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Catatan</label>
                <input type="text" value="{desc}"
                    placeholder="Tambahkan catatan jika ada..."
                    class="item-field w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-400 outline-none focus:border-emerald-400 transition-all"
                    data-field="desc">
            </div>

            <div class="pt-2 flex justify-between items-center border-t border-slate-50 border-dashed">
                <span class="text-[10px] font-bold text-slate-400 uppercase">Subtotal</span>
                <span class="text-sm font-black text-slate-800">{total_formatted}</span>
            </div>
        </div>
    </div>
</template>
