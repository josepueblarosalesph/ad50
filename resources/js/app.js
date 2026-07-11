import mask from '@alpinejs/mask';

// Alpine lo provee Flux/Livewire; registramos el plugin de máscara antes de que arranque.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(mask);
});
