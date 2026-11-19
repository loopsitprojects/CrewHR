

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import confetti from 'canvas-confetti';

window.confetti = confetti;

Alpine.plugin(collapse);

window.Alpine = Alpine;

Alpine.start();
