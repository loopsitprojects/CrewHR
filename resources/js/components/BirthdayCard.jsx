import React, { useState } from 'react';
import { motion } from 'framer-motion';
import confetti from 'canvas-confetti';
import { Globe } from 'lucide-react';

export default function BirthdayCard({
    name = "Supuni Rathnayake",
    timeAgo = "about 11 hours ago",
    avatarUrl = null,
    companyName = "Loops Integrated"
}) {
    const [candlesLit, setCandlesLit] = useState(true);
    const [wishGranted, setWishGranted] = useState(false);

    // Cheerful audio synthesis chime
    const playCelebrateChime = () => {
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
        } catch (e) {}
    };

    // Canvas Confetti Trigger Burst from Click Coordinates
    const fireConfetti = (e, count = 75) => {
        const rect = e?.currentTarget?.getBoundingClientRect();
        const x = rect ? (rect.left + rect.width / 2) / window.innerWidth : 0.5;
        const y = rect ? (rect.top + rect.height / 2) / window.innerHeight : 0.65;

        confetti({
            particleCount: count,
            spread: 70,
            origin: { x, y },
            colors: ['#8b5cf6', '#ec4899', '#f59e0b', '#38bdf8', '#10b981', '#f43f5e', '#a855f7']
        });
    };

    const blowCandles = (e) => {
        if (!candlesLit) return;
        setCandlesLit(false);
        setWishGranted(true);
        playCelebrateChime();
        confetti({ particleCount: 100, spread: 80, origin: { x: 0.35, y: 0.6 } });
        setTimeout(() => confetti({ particleCount: 100, spread: 80, origin: { x: 0.65, y: 0.6 } }), 180);
    };

    return (
        <motion.div 
            initial={{ opacity: 0, scale: 0.98, y: 8 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            transition={{ duration: 0.45, ease: [0.16, 1, 0.3, 1] }}
            className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111927] shadow-sm overflow-hidden transition-all duration-200"
        >
            {/* 1. USER AVATAR & CONTEXT META HEADER */}
            <div className="px-4 sm:px-5 py-3 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 bg-white dark:bg-[#111927]">
                <div className="flex items-center gap-3">
                    {avatarUrl ? (
                        <img src={avatarUrl} alt={name} className="w-10 h-10 rounded-full object-cover ring-2 ring-purple-500/20 shadow-xs shrink-0" />
                    ) : (
                        <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0 ring-2 ring-purple-500/20 uppercase tracking-tight">
                            {name.substring(0, 2).toUpperCase()}
                        </div>
                    )}

                    <div>
                        <h4 className="text-sm font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                            {name}
                        </h4>
                        <div className="flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500 font-medium leading-tight mt-0.5">
                            <span>{timeAgo}</span>
                            <span>·</span>
                            <span className="inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                <Globe className="w-3.5 h-3.5 text-slate-400" />
                                <span>Public to Company</span>
                            </span>
                        </div>
                    </div>
                </div>

                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200/80 dark:border-purple-800/60 shadow-2xs">
                    <span className="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                    <span>Birthday Celebration 🎂</span>
                </span>
            </div>

            {/* 2. HERO ANNOUNCEMENT BANNER WITH ANIMATED STAGE */}
            <div className="relative bg-gradient-to-br from-indigo-50/70 via-purple-50/50 to-pink-50/70 dark:from-[#0d1424] dark:via-[#161530] dark:to-[#1a1226] border-y border-purple-100/80 dark:border-purple-900/40 px-4 sm:px-8 py-8 overflow-hidden select-none">
                
                {/* Floating Confetti Left & Right */}
                <div className="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
                    <div className="bday-float-slow absolute top-6 left-12"><span className="w-2.5 h-3.5 bg-orange-400 rounded-2xs rotate-45 block shadow-xs"></span></div>
                    <div className="bday-float-med absolute top-20 left-24"><span className="w-2 h-3 bg-pink-500 rounded-2xs -rotate-12 block shadow-xs"></span></div>
                    <div className="bday-float-fast absolute top-36 left-16"><span className="w-2.5 h-2 bg-teal-400 rounded-2xs rotate-25 block shadow-xs"></span></div>
                    <div className="bday-float-slow absolute bottom-12 left-20"><span className="w-3 h-1.5 bg-amber-400 rounded-2xs -rotate-45 block shadow-xs"></span></div>
                    
                    <div className="bday-float-med absolute top-8 right-12"><span className="w-2.5 h-3 bg-teal-400 rounded-2xs -rotate-30 block shadow-xs"></span></div>
                    <div className="bday-float-fast absolute top-24 right-24"><span className="w-3 h-1.5 bg-pink-500 rounded-2xs rotate-45 block shadow-xs"></span></div>
                    <div className="bday-float-slow absolute top-40 right-16"><span className="w-2 h-3.5 bg-orange-400 rounded-2xs -rotate-15 block shadow-xs"></span></div>
                    <div className="bday-float-med absolute bottom-12 right-20"><span className="w-3 h-1.5 bg-amber-400 rounded-2xs rotate-30 block shadow-xs"></span></div>
                </div>

                <div className="relative z-10 flex flex-col items-center justify-center text-center max-w-3xl mx-auto">
                    
                    {/* Wobbling Party Cone Hat */}
                    <div className="hat-bob-anim mb-1 inline-block drop-shadow-md">
                        <svg className="w-10 h-12" viewBox="0 0 60 70" fill="none">
                            <polygon points="30,8 10,62 50,62" fill="#38bdf8" />
                            <circle cx="30" cy="26" r="3" fill="#ffffff" />
                            <circle cx="22" cy="42" r="2.5" fill="#ffffff" />
                            <circle cx="38" cy="40" r="2.5" fill="#ffffff" />
                            <circle cx="28" cy="54" r="2" fill="#ffffff" />
                            <path d="M10,62 Q30,68 50,62" stroke="#ffffff" strokeWidth="4" strokeLinecap="round" fill="none" />
                            <circle cx="30" cy="7" r="5" fill="#facc15" />
                        </svg>
                    </div>

                    <h2 className="text-3xl sm:text-4xl font-black text-[#5744ab] dark:text-[#a78bfa] tracking-tight leading-tight">
                        Happy Birthday
                    </h2>
                    <div className="text-lg sm:text-xl font-bold text-slate-700 dark:text-slate-200 mt-0.5">
                        {name}
                    </div>

                    {/* Animated Birthday Scene */}
                    <div className="w-full max-w-lg mx-auto my-4 py-2 flex items-end justify-center gap-2 sm:gap-6 relative">
                        {/* Left Side: Floating Balloons & Champagne Bottle */}
                        <div className="flex items-end gap-2 shrink-0">
                            <div className="balloon-anim-left flex flex-col items-center -mr-2 mb-6">
                                <div className="w-11 h-14 sm:w-13 sm:h-16 rounded-full bg-gradient-to-tr from-purple-600 via-purple-500 to-purple-400 relative shadow-md shadow-purple-500/30">
                                    <div className="absolute top-2 left-2.5 w-3.5 h-5 rounded-full bg-white/40 -rotate-30 blur-2xs"></div>
                                    <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-1.5 bg-purple-700 rounded-b-sm"></div>
                                </div>
                                <svg className="w-4 h-16 sm:h-20 -mt-0.5 stroke-purple-400/70 dark:stroke-purple-400/50" viewBox="0 0 20 80" fill="none">
                                    <path d="M10,0 Q18,20 10,40 T10,80" strokeWidth="1.8" strokeLinecap="round"/>
                                </svg>
                            </div>

                            <div className="balloon-anim-center flex flex-col items-center -mr-3 mb-2">
                                <div className="w-8 h-10 sm:w-9 sm:h-11 rounded-full bg-gradient-to-tr from-orange-500 to-amber-400 relative shadow-md shadow-orange-500/25">
                                    <div className="absolute top-1.5 left-2 w-2.5 h-3.5 rounded-full bg-white/45 -rotate-30 blur-2xs"></div>
                                    <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1 bg-orange-600 rounded-b-sm"></div>
                                </div>
                                <svg className="w-3 h-12 -mt-0.5 stroke-orange-400/70" viewBox="0 0 15 60" fill="none">
                                    <path d="M7,0 Q13,15 7,30 T7,60" strokeWidth="1.5" strokeLinecap="round"/>
                                </svg>
                            </div>

                            <div className="relative flex flex-col items-center mb-1">
                                <div className="absolute -top-6 left-1/2 -translate-x-1/2 pointer-events-none">
                                    <span className="fizz-bubble-1 w-1.5 h-1.5 rounded-full bg-amber-300 block absolute -left-1"></span>
                                    <span className="fizz-bubble-2 w-2 h-2 rounded-full bg-amber-200 block absolute left-1"></span>
                                </div>
                                <div className="w-7 sm:w-8 h-16 sm:h-18 flex flex-col items-center">
                                    <div className="w-2.5 h-4 bg-amber-400 rounded-t-xs"></div>
                                    <div className="w-3.5 h-3 bg-teal-800 rounded-t-sm"></div>
                                    <div className="w-6 sm:w-7 h-10 sm:h-12 bg-teal-700 rounded-t-md rounded-b-sm relative shadow-sm">
                                        <div className="absolute top-2 inset-x-1 h-5 bg-amber-100 rounded-2xs flex items-center justify-center">
                                            <span className="text-[7px] font-black text-amber-800">CUVÉE</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Center: Interactive Cake */}
                        <div 
                            className="relative flex flex-col items-center px-1 sm:px-3 cursor-pointer group"
                            onClick={blowCandles}
                            title="Click to blow out candles & make a wish! 🎂"
                        >
                            {candlesLit && (
                                <div className="candle-glow-pulse absolute -top-8 w-44 h-24 bg-amber-400/25 dark:bg-amber-400/15 rounded-full blur-xl pointer-events-none"></div>
                            )}

                            {/* Candles */}
                            <div className="flex items-end justify-center gap-2 sm:gap-2.5 mb-0.5 z-20">
                                {[1, 2, 3, 4, 5].map((cIdx) => (
                                    <div key={cIdx} className="flex flex-col items-center">
                                        {candlesLit ? (
                                            <div className={`candle-flame-${cIdx} w-2.5 h-4 bg-gradient-to-t from-orange-500 via-amber-400 to-yellow-200 rounded-full shadow-xs`}></div>
                                        ) : (
                                            <div className="smoke-puff w-2.5 h-3 bg-slate-400/60 rounded-full blur-2xs"></div>
                                        )}
                                        <div className="w-0.5 h-1.5 bg-stone-700"></div>
                                        <div className="w-2 h-8 bg-gradient-to-b from-pink-400 to-purple-500 rounded-t-xs shadow-2xs"></div>
                                    </div>
                                ))}
                            </div>

                            {/* Cake Tiers */}
                            <div className="w-28 sm:w-34 h-8 sm:h-9 bg-gradient-to-r from-pink-200 via-rose-100 to-pink-200 dark:from-pink-900/60 dark:via-rose-900/40 dark:to-pink-900/60 rounded-t-lg relative border-t-2 border-x-2 border-pink-300/80 shadow-xs flex items-center justify-around px-2">
                                <span className="w-1.5 h-1 bg-amber-400 rounded-full rotate-45 block"></span>
                                <span className="w-1.5 h-1 bg-purple-500 rounded-full -rotate-30 block"></span>
                                <span className="w-1.5 h-1 bg-teal-400 rounded-full rotate-12 block"></span>
                            </div>

                            <div className="w-36 sm:w-44 h-11 sm:h-13 bg-gradient-to-r from-[#9b405f] via-[#b65275] to-[#9b405f] dark:from-[#6b253d] dark:via-[#7c2f48] dark:to-[#6b253d] rounded-t-md relative border-t-2 border-x-2 border-pink-400/50 shadow-md flex items-center justify-around px-3">
                                <div className="w-full flex justify-around pt-2">
                                    <span className="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                                    <span className="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                                    <span className="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                                    <span className="w-2 h-2 rounded-full bg-pink-100/90 shadow-2xs"></span>
                                </div>
                            </div>

                            <div className="w-44 sm:w-52 h-2.5 sm:h-3 bg-gradient-to-r from-slate-200 via-white to-slate-200 dark:from-slate-700 dark:via-slate-600 dark:to-slate-700 rounded-full shadow-md border border-slate-300 dark:border-slate-600"></div>
                        </div>

                        {/* Right Side: Floating Green & Orange Balloons */}
                        <div className="flex items-end gap-2 shrink-0">
                            <div className="balloon-anim-right flex flex-col items-center -ml-2 mb-7">
                                <div className="w-11 h-14 sm:w-13 sm:h-16 rounded-full bg-gradient-to-tr from-emerald-600 via-green-500 to-emerald-400 relative shadow-md shadow-emerald-500/30">
                                    <div className="absolute top-2 left-2.5 w-3.5 h-5 rounded-full bg-white/40 -rotate-30 blur-2xs"></div>
                                    <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-1.5 bg-emerald-700 rounded-b-sm"></div>
                                </div>
                                <svg className="w-4 h-16 sm:h-20 -mt-0.5 stroke-emerald-400/70 dark:stroke-emerald-400/50" viewBox="0 0 20 80" fill="none">
                                    <path d="M10,0 Q2,20 10,40 T10,80" strokeWidth="1.8" strokeLinecap="round"/>
                                </svg>
                            </div>

                            <div className="balloon-anim-left flex flex-col items-center -ml-3 mb-10">
                                <div className="w-10 h-13 sm:w-12 sm:h-15 rounded-full bg-gradient-to-tr from-amber-600 via-orange-500 to-amber-300 relative shadow-md shadow-orange-500/30">
                                    <div className="absolute top-2 left-2 w-3 h-4.5 rounded-full bg-white/40 -rotate-30 blur-2xs"></div>
                                    <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-1.5 bg-orange-700 rounded-b-sm"></div>
                                </div>
                                <svg className="w-4 h-20 sm:h-24 -mt-0.5 stroke-orange-400/70" viewBox="0 0 20 80" fill="none">
                                    <path d="M10,0 Q18,20 10,40 T10,80" strokeWidth="1.8" strokeLinecap="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Interactive Blow Candle Status Button */}
                    <div className="my-1.5">
                        {candlesLit ? (
                            <motion.button 
                                whileHover={{ scale: 1.05 }}
                                whileTap={{ scale: 0.95 }}
                                onClick={blowCandles}
                                className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100/90 dark:bg-amber-950/60 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700 shadow-2xs hover:bg-amber-200 cursor-pointer"
                            >
                                <span className="text-sm">🎂</span>
                                <span>Click to Blow Candles & Make a Wish!</span>
                                <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            </motion.button>
                        ) : (
                            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-purple-100/90 dark:bg-purple-950/60 text-purple-800 dark:text-purple-200 border border-purple-300 dark:border-purple-700 shadow-xs">
                                <span>✨ Wish Granted! Happy Birthday! 🎉</span>
                                <button onClick={() => setCandlesLit(true)} className="ml-1 text-[11px] underline hover:text-purple-900 dark:hover:text-purple-100">Light Again 🕯️</button>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* 3. CLEAN CELEBRATION FOOTER (ONLY THE WISH) */}
            <div className="px-4 sm:px-6 py-3 bg-slate-50/70 dark:bg-[#0d1424] flex items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800/80">
                <div className="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                    <span className="text-base">🎉</span>
                    <span>{companyName} Family</span>
                </div>

                <motion.button 
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                    onClick={(e) => {
                        fireConfetti(e, 90);
                        playCelebrateChime();
                    }}
                    className="pulse-glow-btn inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-rose-500 hover:from-purple-700 text-white shadow-md shadow-pink-500/25 cursor-pointer"
                >
                    <span>Wish Happy Birthday</span>
                    <span className="text-sm">🎈</span>
                </motion.button>
            </div>
        </motion.div>
    );
}
