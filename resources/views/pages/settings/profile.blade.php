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

        @php
            $initialProfileEdu = old('higher_education');
            if (!$initialProfileEdu) {
                $initialProfileEdu = !empty($employee?->higher_education_list) ? $employee->higher_education_list : [['qualification' => '', 'institute' => '', 'country' => '', 'duration' => '']];
            }
            $initialProfileExp = old('working_experience');
            if (!$initialProfileExp) {
                $initialProfileExp = !empty($employee?->working_experience_list) ? $employee->working_experience_list : [['company' => '', 'designation' => '', 'duration' => '']];
            }

            $ecRaw = $employee->emergency_contact ?? '';
            $ecName = '';
            $ecPhone = '';
            if (str_contains($ecRaw, ' - ')) {
                $parts = explode(' - ', $ecRaw, 2);
                $ecName = $parts[0] ?? '';
                $ecPhone = $parts[1] ?? '';
            } elseif (str_contains($ecRaw, ',')) {
                $parts = explode(',', $ecRaw, 2);
                $ecName = trim($parts[0] ?? '');
                $ecPhone = trim($parts[1] ?? '');
            } elseif (preg_match('/^(.*?)\s*\((.*?)\)$/', $ecRaw, $m)) {
                $ecName = trim($m[1] ?? '');
                $ecPhone = trim($m[2] ?? '');
            } else {
                $ecName = $ecRaw;
            }
        @endphp

        <form action="{{ route('settings.profile.update') }}" method="POST"
              x-data="{
                  educationList: @js($initialProfileEdu),
                  experienceList: @js($initialProfileExp),
                  addEducation() {
                      this.educationList.push({ qualification: '', institute: '', country: '', duration: '' });
                  },
                  removeEducation(index) {
                      if (this.educationList.length > 1) {
                          this.educationList.splice(index, 1);
                      } else {
                          this.educationList[0] = { qualification: '', institute: '', country: '', duration: '' };
                      }
                  },
                  addExperience() {
                      this.experienceList.push({ company: '', designation: '', duration: '' });
                  },
                  removeExperience(index) {
                      if (this.experienceList.length > 1) {
                          this.experienceList.splice(index, 1);
                      } else {
                          this.experienceList[0] = { company: '', designation: '', duration: '' };
                      }
                  }
              }"
              class="space-y-4 text-xs font-semibold">
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
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">NIC / Passport Number</label>
                    <input type="text" name="nic_passport" value="{{ old('nic_passport', $employee->nic_passport ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. 199012345678 or N1234567">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $ecName) }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. Jane Doe (Spouse)">
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $ecPhone) }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. +94 71 987 6543">
                </div>
            </div>

            <!-- Bank Details -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <i class="ph ph-bank text-rose-600"></i> Bank Account Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Name</label>
                        <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $employee->account_holder_name ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. A. B. Perera">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Account Number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $employee->account_number ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. 10002345678">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Bank</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. Commercial Bank of Ceylon">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Branch</label>
                        <input type="text" name="bank_branch" value="{{ old('bank_branch', $employee->bank_branch ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. Kollupitiya Branch">
                    </div>
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 font-bold mb-1">Branch Code</label>
                        <input type="text" name="branch_code" value="{{ old('branch_code', $employee->branch_code ?? '') }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5" placeholder="e.g. 035">
                    </div>
                </div>
            </div>

            <!-- Higher Education & Working Experience -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-4">
                <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <i class="ph ph-graduation-cap text-rose-600"></i> Higher Education & Working Experience
                </h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Higher Education -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-slate-800 dark:text-slate-200 font-extrabold flex items-center gap-1.5 text-xs">
                                <i class="ph ph-graduation-cap text-blue-600 dark:text-blue-400 text-sm"></i> Higher Education
                            </label>
                            <button type="button" @click="addEducation()" class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:text-blue-400 text-[10px] font-bold rounded-lg transition-colors cursor-pointer">
                                <i class="ph ph-plus-circle"></i> Add
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <template x-for="(edu, eIdx) in educationList" :key="'prof-edu-' + eIdx">
                                <div class="p-3 bg-slate-50/80 dark:bg-[#1e2d4d] rounded-xl border border-slate-200/80 dark:border-slate-700/60 space-y-2 relative">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold uppercase text-slate-400" x-text="'Qualification #' + (eIdx + 1)"></span>
                                        <button type="button" @click="removeEducation(eIdx)" x-show="educationList.length > 1" class="text-rose-500 text-[10px] font-bold flex items-center gap-0.5 cursor-pointer">
                                            <i class="ph ph-trash"></i> Remove
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Qualification</label>
                                            <input type="text" :name="'higher_education[' + eIdx + '][qualification]'" x-model="edu.qualification" placeholder="e.g. B.Sc. in IT" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Institute</label>
                                            <input type="text" :name="'higher_education[' + eIdx + '][institute]'" x-model="edu.institute" placeholder="e.g. Univ. of Colombo" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Country</label>
                                            <input type="text" :name="'higher_education[' + eIdx + '][country]'" x-model="edu.country" placeholder="e.g. Sri Lanka" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Duration</label>
                                            <input type="text" :name="'higher_education[' + eIdx + '][duration]'" x-model="edu.duration" placeholder="e.g. 2018 - 2022" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Working Experience -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-slate-800 dark:text-slate-200 font-extrabold flex items-center gap-1.5 text-xs">
                                <i class="ph ph-briefcase text-purple-600 dark:text-purple-400 text-sm"></i> Working Experience
                            </label>
                            <button type="button" @click="addExperience()" class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 hover:bg-purple-100 text-purple-600 dark:bg-purple-500/15 dark:hover:bg-purple-500/25 dark:text-purple-400 text-[10px] font-bold rounded-lg transition-colors cursor-pointer">
                                <i class="ph ph-plus-circle"></i> Add
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <template x-for="(exp, xIdx) in experienceList" :key="'prof-exp-' + xIdx">
                                <div class="p-3 bg-slate-50/80 dark:bg-[#1e2d4d] rounded-xl border border-slate-200/80 dark:border-slate-700/60 space-y-2 relative">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold uppercase text-slate-400" x-text="'Experience #' + (xIdx + 1)"></span>
                                        <button type="button" @click="removeExperience(xIdx)" x-show="experienceList.length > 1" class="text-rose-500 text-[10px] font-bold flex items-center gap-0.5 cursor-pointer">
                                            <i class="ph ph-trash"></i> Remove
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Company</label>
                                            <input type="text" :name="'working_experience[' + xIdx + '][company]'" x-model="exp.company" placeholder="e.g. ABC Tech" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Designation</label>
                                            <input type="text" :name="'working_experience[' + xIdx + '][designation]'" x-model="exp.designation" placeholder="e.g. Lead Engineer" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Duration</label>
                                            <input type="text" :name="'working_experience[' + xIdx + '][duration]'" x-model="exp.duration" placeholder="e.g. 2021 - 2024" class="w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-[#152038] text-slate-900 dark:text-white rounded-lg text-xs font-medium p-2">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
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
