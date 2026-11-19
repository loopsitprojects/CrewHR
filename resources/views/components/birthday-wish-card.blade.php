@props([
    'name' => 'Supuni Rathnayake',
    'timeAgo' => 'about 11 hours ago',
    'avatarUrl' => null,
    'pronoun' => 'her',
    'isAlpine' => false,
])

<div x-data="{
        candlesLit: true,
        wishGranted: false,
        playCelebrateChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const notes = [523.25, 659.25, 783.99, 1046.50];
                notes.forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.06, ctx.currentTime + idx * 0.08);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.08 + 0.35);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + idx * 0.08);
                    osc.stop(ctx.currentTime + idx * 0.08 + 0.35);
                });
            } catch(e) {}
        },
        fireConfetti(e, count = 75) {
            if (!window.confetti) return;
            const target = e?.currentTarget || e?.target;
            const rect = target ? target.getBoundingClientRect() : null;
            const x = rect ? (rect.left + rect.width / 2) / window.innerWidth : 0.5;
            const y = rect ? (rect.top + rect.height / 2) / window.innerHeight : 0.65;
            window.confetti({
                particleCount: count,
                spread: 70,
                origin: { x, y },
                colors: ['#8b5cf6', '#ec4899', '#f59e0b', '#38bdf8', '#10b981', '#f43f5e', '#a855f7']
            });
        },
        fireMassiveCelebration() {
            if (!window.confetti) return;
            const count = 120;
            window.confetti({ particleCount: count, spread: 80, origin: { x: 0.3, y: 0.6 }, colors: ['#8b5cf6', '#ec4899', '#f59e0b', '#38bdf8'] });
            setTimeout(() => {
                window.confetti({ particleCount: count, spread: 80, origin: { x: 0.7, y: 0.6 }, colors: ['#10b981', '#f43f5e', '#fbbf24', '#06b6d4'] });
            }, 180);
        },
        blowCandles(e) {
            if (!this.candlesLit) return;
            this.candlesLit = false;
            this.wishGranted = true;
            this.playCelebrateChime();
            this.fireMassiveCelebration();
        },
        relightCandles() {
            this.candlesLit = true;
            this.wishGranted = false;
        }
    }"
    {{ $attributes->merge(['class' => 'bday-card-enter bg-white dark:bg-[#111927] rounded-2xl border border-slate-200/90 dark:border-slate-800 overflow-hidden shadow-sm transition-all duration-200']) }}>
    
    <!-- ==========================================
         1. USER AVATAR & CONTEXT META HEADER
         ========================================== -->
    <div class="px-4 sm:px-5 py-3 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 bg-white dark:bg-[#111927]">
        <div class="flex items-center gap-3">
            <!-- Author Avatar (40x40px rounded with fallback initials) -->
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $name }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-purple-500/20 shadow-xs shrink-0">
            @else
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0 ring-2 ring-purple-500/20 uppercase tracking-tight">
                    @if($isAlpine)
                        <span x-text="(typeof bday === 'string' ? bday : (bday?.name || '{{ $name }}')).substring(0, 2)"></span>
                    @else
                        {{ strtoupper(substr($name, 0, 2)) }}
                    @endif
                </div>
            @endif

            <!-- Meta Details -->
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                    @if($isAlpine)
                        <span x-text="typeof bday === 'string' ? bday : (bday?.name || '{{ $name }}')"></span>
                    @else
                        {{ $name }}
                    @endif
                </h4>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500 font-medium leading-tight mt-0.5">
                    <span>{{ $timeAgo }}</span>
                    <span>·</span>
                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                        <svg class="w-3.5 h-3.5 fill-current text-slate-400" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        <span>Public to Company</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Milestone Category Pill -->
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200/80 dark:border-purple-800/60 shadow-2xs">
            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
            <span>Birthday Celebration 🎂</span>
        </span>
    </div>

    <!-- ==========================================
         2. HERO ANNOUNCEMENT BANNER (ANIMATED WISH STAGE)
         ========================================== -->
    <div class="relative bg-gradient-to-br from-indigo-50/70 via-purple-50/50 to-pink-50/70 dark:from-[#0d1424] dark:via-[#161530] dark:to-[#1a1226] border-y border-purple-100/80 dark:border-purple-900/40 px-4 sm:px-8 py-8 overflow-hidden select-none">
        
        <!-- Animated Background Ambient Glows -->
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-purple-400/15 dark:bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-pink-400/15 dark:bg-pink-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Animated Floating Confetti on Left & Right Flanks -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <!-- Left Flank Confetti -->
            <div class="bday-float-slow absolute top-6 left-6 sm:left-12">
                <span class="w-2.5 h-3.5 bg-orange-400 rounded-2xs rotate-45 block shadow-xs"></span>
            </div>
            <div class="bday-float-med absolute top-20 left-16 sm:left-28">
                <span class="w-2 h-3 bg-pink-500 rounded-2xs -rotate-12 block shadow-xs"></span>
            </div>
            <div class="bday-float-fast absolute top-36 left-8 sm:left-16">
                <span class="w-2.5 h-2 bg-teal-400 rounded-2xs rotate-25 block shadow-xs"></span>
            </div>
            <div class="bday-float-slow absolute bottom-12 left-12 sm:left-24">
                <span class="w-3 h-1.5 bg-amber-400 rounded-2xs -rotate-45 block shadow-xs"></span>
            </div>
            <div class="sparkle-twinkle absolute top-12 left-24 sm:left-40 text-amber-400 text-sm">✦</div>
            <div class="sparkle-twinkle absolute bottom-20 left-6 sm:left-16 text-purple-400 text-base">★</div>

            <!-- Right Flank Confetti -->
            <div class="bday-float-med absolute top-8 right-6 sm:right-12">
                <span class="w-2.5 h-3 bg-teal-400 rounded-2xs -rotate-30 block shadow-xs"></span>
            </div>
            <div class="bday-float-fast absolute top-24 right-16 sm:right-28">
                <span class="w-3 h-1.5 bg-pink-500 rounded-2xs rotate-45 block shadow-xs"></span>
            </div>
            <div class="bday-float-slow absolute top-40 right-8 sm:right-16">
                <span class="w-2 h-3.5 bg-orange-400 rounded-2xs -rotate-15 block shadow-xs"></span>
            </div>
            <div class="bday-float-med absolute bottom-12 right-14 sm:right-24">
                <span class="w-3 h-1.5 bg-amber-400 rounded-2xs rotate-30 block shadow-xs"></span>
            </div>
            <div class="sparkle-twinkle absolute top-14 right-24 sm:right-40 text-pink-400 text-sm">✦</div>
            <div class="sparkle-twinkle absolute bottom-20 right-6 sm:right-16 text-teal-400 text-base">★</div>
        </div>

        <!-- Center Celebration Stage Content -->
        <div class="relative z-10 flex flex-col items-center justify-center text-center max-w-3xl mx-auto">
            
            <!-- Playful Animated Party Cone Hat -->
            <div class="hat-bob-anim mb-1 inline-block drop-shadow-md">
                <svg class="w-10 h-12" viewBox="0 0 60 70" fill="none">
                    <polygon points="30,8 10,62 50,62" fill="#38bdf8" />
                    <circle cx="30" cy="26" r="3" fill="#ffffff" />
                    <circle cx="22" cy="42" r="2.5" fill="#ffffff" />
                    <circle cx="38" cy="40" r="2.5" fill="#ffffff" />
                    <circle cx="28" cy="54" r="2" fill="#ffffff" />
                    <path d="M10,62 Q30,68 50,62" stroke="#ffffff" stroke-width="4" stroke-linecap="round" fill="none" />
                    <circle cx="30" cy="7" r="5" fill="#facc15" />
                </svg>
            </div>

            <!-- Primary Headline: Happy Birthday -->
            <h2 class="text-3xl sm:text-4xl font-black text-[#5744ab] dark:text-[#a78bfa] tracking-tight leading-tight">
                Happy Birthday
            </h2>

            <!-- Celebrant Sub-Name -->
            <div class="text-lg sm:text-xl font-bold text-slate-700 dark:text-slate-200 mt-0.5">
                @if($isAlpine)
                    <span x-text="typeof bday === 'string' ? bday : (bday?.name || '{{ $name }}')"></span>
                @else
                    {{ $name }}
                @endif
            </div>

            <!-- =========================================================
                 THE ANIMATED BIRTHDAY SCENE (Cake, Candles, Balloons, Bottle)
                 ========================================================= -->
            <div class="w-full max-w-lg mx-auto my-4 py-2 flex items-end justify-center gap-2 sm:gap-6 relative">
                
                <!-- 1. LEFT SIDE: Floating Balloons & Champagne Bottle -->
                <div class="flex items-end gap-2 shrink-0">
                    <!-- Purple Balloon with swaying string -->
                    <div class="balloon-anim-left flex flex-col items-center -mr-2 mb-6">
                        <div class="w-11 h-14 sm:w-13 sm:h-16 rounded-full bg-gradient-to-tr from-purple-600 via-purple-500 to-purple-400 relative shadow-md shadow-purple-500/30">
                            <div class="absolute top-2 left-2.5 w-3.5 h-5 rounded-full bg-white/40 -rotate-30 blur-2xs"></div>
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-1.5 bg-purple-700 rounded-b-sm"></div>
                        </div>
                        <svg class="w-4 h-16 sm:h-20 -mt-0.5 stroke-purple-400/70 dark:stroke-purple-400/50" viewBox="0 0 20 80" fill="none">
                            <path d="M10,0 Q18,20 10,40 T10,80" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <!-- Coral/Orange Mini Balloon -->
                    <div class="balloon-anim-center flex flex-col items-center -mr-3 mb-2">
                        <div class="w-8 h-10 sm:w-9 sm:h-11 rounded-full bg-gradient-to-tr from-orange-500 to-amber-400 relative shadow-md shadow-orange-500/25">
                            <div class="absolute top-1.5 left-2 w-2.5 h-3.5 rounded-full bg-white/45 -rotate-30 blur-2xs"></div>
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1 bg-orange-600 rounded-b-sm"></div>
                        </div>
                        <svg class="w-3 h-12 -mt-0.5 stroke-orange-400/70" viewBox="0 0 15 60" fill="none">
                            <path d="M7,0 Q13,15 7,30 T7,60" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <!-- Champagne Bottle with Popping Fizz Bubbles -->
                    <div class="relative flex flex-col items-center mb-1">
                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 pointer-events-none">
                            <span class="fizz-bubble-1 w-1.5 h-1.5 rounded-full bg-amber-300 block absolute -left-1"></span>
                            <span class="fizz-bubble-2 w-2 h-2 rounded-full bg-amber-200 block absolute left-1"></span>
                            <span class="fizz-bubble-3 w-1.5 h-1.5 rounded-full bg-yellow-400 block absolute -left-2"></span>
                        </div>
                        <div class="w-7 sm:w-8 h-16 sm:h-18 flex flex-col items-center">
                            <div class="w-2.5 h-4 bg-amber-400 rounded-t-xs"></div>
                            <div class="w-3.5 h-3 bg-teal-800 rounded-t-sm"></div>
                            <div class="w-6 sm:w-7 h-10 sm:h-12 bg-teal-700 rounded-t-md rounded-b-sm relative shadow-sm">
                                <div class="absolute top-2 inset-x-1 h-5 bg-amber-100 rounded-2xs flex items-center justify-center">
                                    <span class="text-[7px] font-black text-amber-800">CUVÉE</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. CENTER: THE INTERACTIVE ANIMATED BIRTHDAY CAKE -->
                <div class="relative flex flex-col items-center px-1 sm:px-3 cursor-pointer group"
                     @click="blowCandles($event)"
                     title="Click to blow out candles & make a wish! 🎂">
                    
                    <!-- Interactive Hover Tooltip Pill -->
                    <div class="absolute -top-8 px-2.5 py-0.5 rounded-full bg-purple-900/85 text-white text-[10px] font-bold tracking-tight shadow-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-30">
                        <span x-text="candlesLit ? '✨ Click cake to blow out candles!' : '🕯️ Wish granted! Click relight below'"></span>
                    </div>

                    <!-- Warm Candle Glow Radial Backdrop (Pulses when lit) -->
                    <div x-show="candlesLit" class="candle-glow-pulse absolute -top-8 w-44 h-24 bg-amber-400/25 dark:bg-amber-400/15 rounded-full blur-xl pointer-events-none"></div>

                    <!-- Candles Row -->
                    <div class="flex items-end justify-center gap-2 sm:gap-2.5 mb-0.5 z-20">
                        <!-- Candle 1 -->
                        <div class="flex flex-col items-center">
                            <template x-if="candlesLit">
                                <div class="candle-flame-1 w-2.5 h-4 bg-gradient-to-t from-orange-500 via-amber-400 to-yellow-200 rounded-full shadow-xs"></div>
                            </template>
                            <template x-if="!candlesLit">
                                <div class="smoke-puff w-2.5 h-3 bg-slate-400/60 rounded-full blur-2xs"></div>
                            </template>
                            <div class="w-0.5 h-1.5 bg-stone-700"></div>
                            <div class="w-2 h-7 sm:h-8 bg-gradient-to-b from-pink-400 to-pink-500 rounded-t-xs shadow-2xs border-x border-pink-300"></div>
                        </div>

                        <!-- Candle 2 -->
                        <div class="flex flex-col items-center">
                            <template x-if="candlesLit">
                                <div class="candle-flame-2 w-2.5 h-4.5 bg-gradient-to-t from-orange-500 via-amber-400 to-yellow-200 rounded-full shadow-xs"></div>
                            </template>
                            <template x-if="!candlesLit">
                                <div class="smoke-puff w-2.5 h-3 bg-slate-400/60 rounded-full blur-2xs"></div>
                            </template>
                            <div class="w-0.5 h-1.5 bg-stone-700"></div>
                            <div class="w-2 h-8 sm:h-9 bg-gradient-to-b from-blue-400 to-blue-500 rounded-t-xs shadow-2xs border-x border-blue-300"></div>
                        </div>

                        <!-- Candle 3 (Center - Tallest) -->
                        <div class="flex flex-col items-center">
                            <template x-if="candlesLit">
                                <div class="candle-flame-3 w-3 h-5 bg-gradient-to-t from-orange-500 via-amber-400 to-yellow-200 rounded-full shadow-sm"></div>
                            </template>
                            <template x-if="!candlesLit">
                                <div class="smoke-puff w-3 h-4 bg-slate-400/60 rounded-full blur-2xs"></div>
                            </template>
                            <div class="w-0.5 h-1.5 bg-stone-700"></div>
                            <div class="w-2.5 h-9 sm:h-10 bg-gradient-to-b from-purple-400 to-purple-500 rounded-t-xs shadow-2xs border-x border-purple-300"></div>
                        </div>

                        <!-- Candle 4 -->
                        <div class="flex flex-col items-center">
                            <template x-if="candlesLit">
                                <div class="candle-flame-4 w-2.5 h-4.5 bg-gradient-to-t from-orange-500 via-amber-400 to-yellow-200 rounded-full shadow-xs"></div>
                            </template>
                            <template x-if="!candlesLit">
                                <div class="smoke-puff w-2.5 h-3 bg-slate-400/60 rounded-full blur-2xs"></div>
                            </template>
                            <div class="w-0.5 h-1.5 bg-stone-700"></div>
                            <div class="w-2 h-8 sm:h-9 bg-gradient-to-b from-amber-400 to-amber-500 rounded-t-xs shadow-2xs border-x border-amber-300"></div>
                        </div>

                        <!-- Candle 5 -->
                        <div class="flex flex-col items-center">
                            <template x-if="candlesLit">
                                <div class="candle-flame-5 w-2.5 h-4 bg-gradient-to-t from-orange-500 via-amber-400 to-yellow-200 rounded-full shadow-xs"></div>
                            </template>
                            <template x-if="!candlesLit">
                                <div class="smoke-puff w-2.5 h-3 bg-slate-400/60 rounded-full blur-2xs"></div>
                            </template>
                            <div class="w-0.5 h-1.5 bg-stone-700"></div>
                            <div class="w-2 h-7 sm:h-8 bg-gradient-to-b from-teal-400 to-teal-500 rounded-t-xs shadow-2xs border-x border-teal-300"></div>
                        </div>
                    </div>

                    <!-- Cake Tier 1 (Top Layer) -->
                    <div class="w-28 sm:w-34 h-8 sm:h-9 bg-gradient-to-r from-pink-200 via-rose-100 to-pink-200 dark:from-pink-900/60 dark:via-rose-900/40 dark:to-pink-900/60 rounded-t-lg relative border-t-2 border-x-2 border-pink-300/80 dark:border-pink-500/40 shadow-xs flex items-center justify-around px-2">
                        <div class="absolute -top-1 inset-x-0 flex justify-between px-1">
                            <span class="w-3.5 h-2.5 bg-white dark:bg-pink-300 rounded-b-full shadow-2xs"></span>
                            <span class="w-4 h-3 bg-white dark:bg-pink-300 rounded-b-full shadow-2xs"></span>
                            <span class="w-3.5 h-2 bg-white dark:bg-pink-300 rounded-b-full shadow-2xs"></span>
                            <span class="w-4 h-3.5 bg-white dark:bg-pink-300 rounded-b-full shadow-2xs"></span>
                            <span class="w-3 h-2 bg-white dark:bg-pink-300 rounded-b-full shadow-2xs"></span>
                        </div>
                        <span class="w-1.5 h-1 bg-amber-400 rounded-full rotate-45 block"></span>
                        <span class="w-1.5 h-1 bg-purple-500 rounded-full -rotate-30 block"></span>
                        <span class="w-1.5 h-1 bg-teal-400 rounded-full rotate-12 block"></span>
                    </div>

                    <!-- Cake Tier 2 (Bottom Layer) -->
                    <div class="w-36 sm:w-44 h-11 sm:h-13 bg-gradient-to-r from-[#9b405f] via-[#b65275] to-[#9b405f] dark:from-[#6b253d] dark:via-[#7c2f48] dark:to-[#6b253d] rounded-t-md relative border-t-2 border-x-2 border-pink-400/50 shadow-md flex items-center justify-around px-3">
                        <div class="absolute -top-1.5 inset-x-0 flex justify-between px-1.5">
                            <span class="w-4 h-3 bg-rose-300 dark:bg-rose-400 rounded-b-full"></span>
                            <span class="w-4.5 h-3.5 bg-rose-300 dark:bg-rose-400 rounded-b-full"></span>
                            <span class="w-4 h-2.5 bg-rose-300 dark:bg-rose-400 rounded-b-full"></span>
                            <span class="w-5 h-4 bg-rose-300 dark:bg-rose-400 rounded-b-full"></span>
                            <span class="w-4 h-3 bg-rose-300 dark:bg-rose-400 rounded-b-full"></span>
                            <span class="w-4.5 h-3.5 bg-rose-300 dark:bg-rose-400 rounded-b-full"></span>
                        </div>
                        <div class="w-full flex justify-around pt-2">
                            <span class="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                            <span class="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                            <span class="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                            <span class="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                            <span class="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                        </div>
                    </div>

                    <!-- Cake Plate Stand -->
                    <div class="w-44 sm:w-52 h-2.5 sm:h-3 bg-gradient-to-r from-slate-200 via-white to-slate-200 dark:from-slate-700 dark:via-slate-600 dark:to-slate-700 rounded-full shadow-md border border-slate-300 dark:border-slate-600"></div>
                </div>

                <!-- 3. RIGHT SIDE: Floating Green, Orange, and Blue Balloons -->
                <div class="flex items-end gap-2 shrink-0">
                    <div class="balloon-anim-right flex flex-col items-center -ml-2 mb-7">
                        <div class="w-11 h-14 sm:w-13 sm:h-16 rounded-full bg-gradient-to-tr from-emerald-600 via-green-500 to-emerald-400 relative shadow-md shadow-emerald-500/30">
                            <div class="absolute top-2 left-2.5 w-3.5 h-5 rounded-full bg-white/40 -rotate-30 blur-2xs"></div>
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-1.5 bg-emerald-700 rounded-b-sm"></div>
                        </div>
                        <svg class="w-4 h-16 sm:h-20 -mt-0.5 stroke-emerald-400/70 dark:stroke-emerald-400/50" viewBox="0 0 20 80" fill="none">
                            <path d="M10,0 Q2,20 10,40 T10,80" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <div class="balloon-anim-left flex flex-col items-center -ml-3 mb-10">
                        <div class="w-10 h-13 sm:w-12 sm:h-15 rounded-full bg-gradient-to-tr from-amber-600 via-orange-500 to-amber-300 relative shadow-md shadow-orange-500/30">
                            <div class="absolute top-2 left-2 w-3 h-4.5 rounded-full bg-white/40 -rotate-30 blur-2xs"></div>
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-1.5 bg-orange-700 rounded-b-sm"></div>
                        </div>
                        <svg class="w-4 h-20 sm:h-24 -mt-0.5 stroke-orange-400/70" viewBox="0 0 20 80" fill="none">
                            <path d="M10,0 Q18,20 10,40 T10,80" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <div class="balloon-anim-center flex flex-col items-center -ml-2 mb-2">
                        <div class="w-8 h-10 sm:w-9 sm:h-11 rounded-full bg-gradient-to-tr from-sky-500 to-cyan-300 relative shadow-md shadow-sky-500/25">
                            <div class="absolute top-1.5 left-2 w-2.5 h-3.5 rounded-full bg-white/45 -rotate-30 blur-2xs"></div>
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1 bg-sky-600 rounded-b-sm"></div>
                        </div>
                        <svg class="w-3 h-12 -mt-0.5 stroke-sky-400/70" viewBox="0 0 15 60" fill="none">
                            <path d="M7,0 Q2,15 7,30 T7,60" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

            </div>

            <!-- Interactive Candle Status / Blow Out Button -->
            <div class="my-1.5">
                <template x-if="candlesLit">
                    <button type="button" 
                            @click="blowCandles($event)" 
                            class="bday-btn-spring inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100/90 dark:bg-amber-950/60 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700 shadow-2xs hover:bg-amber-200 cursor-pointer">
                        <span class="text-sm">🎂</span>
                        <span>Click to Blow Candles & Make a Wish!</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    </button>
                </template>
                <template x-if="!candlesLit">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-purple-100/90 dark:bg-purple-950/60 text-purple-800 dark:text-purple-200 border border-purple-300 dark:border-purple-700 shadow-xs">
                        <span>✨ Wish Granted! Happy Birthday! 🎉</span>
                        <button type="button" @click="relightCandles()" class="ml-1 text-[11px] underline hover:text-purple-900 dark:hover:text-purple-100">Light Again 🕯️</button>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- ==========================================
         3. CLEAN CELEBRATION FOOTER (ONLY THE WISH)
         ========================================== -->
    <div class="px-4 sm:px-6 py-3 bg-slate-50/70 dark:bg-[#0d1424] flex items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800/80">
        
        <!-- Left: Loops Integrated Family Badge -->
        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
            <span class="text-base">🎉</span>
            <span>Loops Integrated Family</span>
        </div>

        <!-- Right: Wish Happy Birthday Button (Shoots Confetti Celebration) -->
        <button type="button" 
                @click="fireConfetti($event, 90); playCelebrateChime();"
                class="bday-btn-spring pulse-glow-btn inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-rose-500 hover:from-purple-700 hover:via-pink-700 hover:to-rose-600 text-white shadow-md shadow-pink-500/25 cursor-pointer">
            <span>Wish Happy Birthday</span>
            <span class="text-sm">🎈</span>
        </button>

    </div>

</div>
