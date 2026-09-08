<div class="space-y-6">
    <!-- 1. Personal Profile Information -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-user-circle text-rose-600 dark:text-rose-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Personal Profile & Contact Information</h2>
            </div>
            <span class="bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-rose-200/60 dark:border-rose-900/60">
                User Account ID: {{ $user->id ?? 1 }}
            </span>
        </div>

        <form action="{{ route('settings.profile.update') }}" method="POST" class="space-y-4 text-xs font-semibold">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-rose-500/20" required>
                    @error('name') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-rose-500/20" required>
                    @error('email') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Primary Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $employee->phone_number ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="+94 77 123 4567">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Emergency Contact (Name & Phone)</label>
                    <input type="text" name="emergency_contact" value="{{ old('emergency_contact', $employee->emergency_contact ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. Spouse (+94 71 987 6543)">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">NIC / Passport Number</label>
                    <input type="text" name="nic_passport" value="{{ old('nic_passport', $employee->nic_passport ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5">
                </div>
            </div>

            <!-- Bank Details -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <i class="ph ph-bank text-rose-600"></i> Bank Account Information (for Direct Salary Transfer)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. Commercial Bank of Ceylon">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Branch Name</label>
                        <input type="text" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. Kollupitiya Branch">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Account Number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $employee->account_number ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. 10002345678">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Account Holder Name</label>
                        <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $employee->account_holder_name ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. A. B. Perera">
                    </div>
                </div>
            </div>

            <!-- Qualifications -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <i class="ph ph-graduation-cap text-rose-600"></i> Higher Education & Professional Qualifications
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Higher Education & Degrees</label>
                        <textarea name="higher_education" rows="2" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5" placeholder="e.g. BSc in Computer Science (Univ. of Colombo)">{{ old('higher_education', $employee->higher_education ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Professional Certifications</label>
                        <textarea name="professional_qualifications" rows="2" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5" placeholder="e.g. CIPM Chartered HR, PMP, AWS Certified">{{ old('professional_qualifications', $employee->professional_qualifications ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-rose-500/20 transition-all cursor-pointer">
                    <i class="ph ph-floppy-disk text-sm"></i>
                    <span>Save Profile Information</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 2. Security & Password Update -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <i class="ph ph-lock-key text-rose-600 dark:text-rose-400 text-lg"></i>
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Account Password & Security</h2>
        </div>

        <form action="{{ route('settings.password.update') }}" method="POST" 
              x-data="{ showCurrent: false, showNew: false, showConfirm: false }"
              class="space-y-4 text-xs font-semibold">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Current Password -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Current Password *</label>
                    <div class="relative flex items-center">
                        <input :type="showCurrent ? 'text' : 'password'" name="current_password" placeholder="••••••••" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 pr-10 focus:ring-2 focus:ring-rose-500/20" required>
                        <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i data-lucide="eye" x-show="!showCurrent" class="w-4 h-4"></i>
                            <i data-lucide="eye-off" x-show="showCurrent" x-cloak class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                        </button>
                    </div>
                    @error('current_password') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">New Password *</label>
                    <div class="relative flex items-center">
                        <input :type="showNew ? 'text' : 'password'" name="password" placeholder="••••••••" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 pr-10 focus:ring-2 focus:ring-rose-500/20" required>
                        <button type="button" @click="showNew = !showNew" class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i data-lucide="eye" x-show="!showNew" class="w-4 h-4"></i>
                            <i data-lucide="eye-off" x-show="showNew" x-cloak class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                        </button>
                    </div>
                    @error('password') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Confirm New Password *</label>
                    <div class="relative flex items-center">
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" placeholder="••••••••" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 pr-10 focus:ring-2 focus:ring-rose-500/20" required>
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i data-lucide="eye" x-show="!showConfirm" class="w-4 h-4"></i>
                            <i data-lucide="eye-off" x-show="showConfirm" x-cloak class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md transition-all cursor-pointer">
                    <i class="ph ph-key text-sm"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>
</div>
