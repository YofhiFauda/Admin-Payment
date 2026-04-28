{{-- 2. MAIN INFO FIELDS --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 mb-8 md:mb-10">

    <div>
        <label
            class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Nama
            Vendor</label>
        <input type="text" name="customer" id="customer" value="{{ old('customer', '') }}"
            placeholder="Opsional (Diisi otomatis oleh sistem nanti)"
            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
    </div>

    <div>
        <label
            class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Tanggal
            Transaksi</label>
        <input type="date" name="date" id="date" required
            value="{{ old('date', now()->format('Y-m-d')) }}"
            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all" />
    </div>

    {{-- Kategori (Baru ditambahkan) --}}
    <div>
        <label
            class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Kategori</label>
        <div class="relative">
            <select name="category" id="category" required
                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                <option value="" disabled {{ old('category') ? '' : 'selected' }}>Pilih kategori...
                </option>
                @foreach($rembushCategories as $cat)
                    <option value="{{ $cat->name }}" {{ old('category') == $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <i data-lucide="chevron-down"
                class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
    </div>

    {{-- Metode Pencairan (Baru ditambahkan) --}}
    <div>
        <label
            class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Metode
            Pencairan</label>
        <div class="relative">
            <select name="payment_method" id="payment_method" required
                class="w-full appearance-none bg-white border border-slate-200 rounded-xl p-3 md:p-3.5 pr-10 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none transition-all">
                <option value="" disabled {{ old('payment_method') ? '' : 'selected' }}>Pilih metode
                    pembayaran...</option>
                @foreach(\App\Models\Transaction::PAYMENT_METHODS as $key => $label)
                    <option value="{{ $key }}" {{ old('payment_method') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <i data-lucide="chevron-down"
                class="w-4 h-4 absolute right-3 md:right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
    </div>

    {{-- Form Rekening/E-Wallet khusus Transfer Penjual --}}
    <div id="bank_details_section"
        class="md:col-span-2 hidden bg-blue-50/50 border border-blue-100/50 rounded-2xl p-4 md:p-5 mt-2">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="landmark" class="w-4 h-4 text-blue-500"></i>
            <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider">Informasi Rekening
                / E-Wallet Penjual</h4>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label
                    class="block text-[10px] md:text-xs font-bold text-blue-700 uppercase mb-2 tracking-wider">Nama
                    Bank / E-Wallet <span class="text-red-500">*</span></label>
                <input type="text" name="bank_name" id="bank_name" placeholder="Misal: BCA, OVO"
                    value="{{ old('bank_name') }}"
                    class="w-full border border-blue-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-300 outline-none transition-all bg-white uppercase" />
            </div>
            <div>
                <label
                    class="block text-[10px] md:text-xs font-bold text-blue-700 uppercase mb-2 tracking-wider">Atas
                    Nama Rekening <span class="text-red-500">*</span></label>
                <input type="text" name="account_name" id="account_name" placeholder="Atas nama"
                    value="{{ old('account_name') }}"
                    class="w-full border border-blue-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-300 outline-none transition-all bg-white uppercase" />
            </div>
            <div>
                <label
                    class="block text-[10px] md:text-xs font-bold text-blue-700 uppercase mb-2 tracking-wider">Nomor
                    Rekening <span class="text-red-500">*</span></label>
                <input type="text" name="account_number" id="account_number"
                    placeholder="Nomor rekening / No HP" value="{{ old('account_number') }}"
                    inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="w-full border border-blue-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-300 outline-none transition-all bg-white" />
            </div>
        </div>
    </div>

    <div class="md:col-span-2">
        <label
            class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
        <textarea name="description" id="description" rows="2" placeholder="Nota pembelian dari..."
            class="w-full border border-slate-200 rounded-xl p-3 md:p-3.5 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none resize-none transition-all">{{ old('description') }}</textarea>
    </div>
</div>
