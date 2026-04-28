{{-- 0. MANAGEMENT: INPUT FOR TECHNICIAN --}}
@if(Auth::user()->role !== 'teknisi' && isset($technicians) && $technicians->count() > 0)
    <div class="mb-8 md:mb-10 bg-emerald-50/50 border border-emerald-100 rounded-2xl p-4 md:p-6">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="user-plus" class="w-4 h-4 text-emerald-600"></i>
            <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Input Atas Nama
                Teknisi</h4>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label
                    class="block text-[10px] md:text-xs font-bold text-emerald-700 uppercase mb-2 tracking-wider">Pilih
                    Teknisi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <select name="technician_id" id="technician_id" required
                        class="w-full appearance-none bg-white border border-emerald-200 rounded-xl p-3 text-xs md:text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-300 outline-none transition-all">
                        <option value="" disabled {{ old('technician_id') ? '' : 'selected' }}>-- Pilih
                            Teknisi --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }} data-accounts='@json($tech->bankAccounts)'>
                                {{ $tech->name }}
                            </option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down"
                        class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-emerald-400 pointer-events-none"></i>
                </div>
            </div>
            <div id="technician_bank_container"
                class="{{ old('technician_id') ? '' : 'opacity-50 pointer-events-none' }}">
                <label
                    class="block text-[10px] md:text-xs font-bold text-emerald-700 uppercase mb-2 tracking-wider">Rekening
                    Teknisi (Untuk Transfer)</label>
                <div class="relative">
                    <select name="technician_bank_account_id" id="technician_bank_account_id"
                        class="w-full appearance-none bg-white border border-emerald-200 rounded-xl p-3 text-xs md:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-300 outline-none transition-all">
                        <option value="">-- Pilih Rekening (Opsional) --</option>
                    </select>
                    <i data-lucide="chevron-down"
                        class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-emerald-400 pointer-events-none"></i>
                </div>
            </div>
        </div>
        <p class="text-[10px] text-emerald-600/70 mt-3 italic">* Data ini diperlukan agar pembayaran
            "Transfer ke Teknisi" dapat diproses dengan benar.</p>
    </div>
@endif
