{{-- 0. MANAGEMENT: PEMBELIAN ATAS NAMA --}}
@if(Auth::user()->role !== 'teknisi' && isset($technicians) && $technicians->count() > 0)
<div class="mb-8 md:mb-10 bg-indigo-50/50 border border-indigo-100 rounded-2xl p-4 md:p-6">
    <div class="flex items-center gap-2 mb-4">
        <i data-lucide="user-cog" class="w-4 h-4 text-indigo-600"></i>
        <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-wider">Otoritas Pembelian</h4>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-[10px] md:text-xs font-bold text-indigo-700 uppercase mb-2 tracking-wider">
                Pembelian Atas Nama 
                <span class="text-indigo-600/50 font-normal normal-case">(Opsional)</span>
            </label>
            <div class="relative group">
                <select name="technician_id" id="technician_id"
                    class="w-full appearance-none bg-white border border-indigo-200 rounded-xl p-3 text-xs md:text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none transition-all">
                    <option value="" {{ old('technician_id') ? '' : 'selected' }}>-- Atas Nama Sendiri (Default) --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }} data-accounts='@json($tech->bankAccounts)'>
                            {{ $tech->name }}
                        </option>
                    @endforeach
                </select>
                <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-indigo-400 pointer-events-none"></i>
            </div>
        </div>
        <div id="technician_bank_container" class="{{ old('technician_id') ? '' : 'opacity-50 pointer-events-none' }}">
            <label class="block text-[10px] md:text-xs font-bold text-indigo-700 uppercase mb-2 tracking-wider">
                Rekening Teknisi <span class="text-indigo-600/50 font-normal normal-case">(Untuk Transfer)</span>
            </label>
            <div class="relative group">
                <select name="technician_bank_account_id" id="technician_bank_account_id"
                    data-old="{{ old('technician_bank_account_id') }}"
                    class="w-full appearance-none bg-white border border-indigo-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none transition-all">
                    <option value="">-- Pilih Rekening (Opsional) --</option>
                </select>
                <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-indigo-400 pointer-events-none"></i>
            </div>
        </div>
    </div>
    <p class="text-[10px] text-indigo-600/70 mt-3 italic leading-relaxed">
        * Pilih teknisi jika pembelian ini dilakukan oleh orang lain. <br>
        Rekening diperlukan agar pembayaran "Transfer ke Teknisi" dapat diproses otomatis.
    </p>
</div>
@endif
