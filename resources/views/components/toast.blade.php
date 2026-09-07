<div
    x-data="{ show: false, message: '', type: 'success' }"
    x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type || 'success'; setTimeout(() => { show = false }, 3000)"
    class="fixed bottom-4 right-4 z-50"
>
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-white"
        :class="{
            'bg-green-600': type === 'success',
            'bg-red-600': type === 'error',
            'bg-blue-600': type === 'info'
        }"
        style="display: none;"
    >
        <i class="ph text-xl" :class="{
            'ph-check-circle': type === 'success',
            'ph-warning-circle': type === 'error',
            'ph-info': type === 'info'
        }"></i>
        <span x-text="message" class="text-sm font-medium"></span>
        <button @click="show = false" class="ml-4 opacity-75 hover:opacity-100 focus:outline-none">
            <i class="ph ph-x"></i>
        </button>
    </div>
</div>
