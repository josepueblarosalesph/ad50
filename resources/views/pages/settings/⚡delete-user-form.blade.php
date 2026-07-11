<?php

use Livewire\Component;

new class extends Component {}; ?>

<div class="ad-card border-l-[3px] border-l-red-300 dark:border-l-red-500">
    <div class="ad-card-head bg-red-50/60 dark:bg-red-950/30"><div><h2 class="text-[16px] font-extrabold text-[#A93226] dark:text-red-400">Eliminar cuenta</h2><p class="mt-1 text-[13px] text-gray-500">Elimina tu cuenta y toda su información de forma permanente.</p></div></div>
    <div class="p-6">
        <flux:modal.trigger name="confirm-user-deletion">
            <flux:button variant="danger" data-test="delete-user-button">Eliminar mi cuenta</flux:button>
        </flux:modal.trigger>
    </div>

    <livewire:pages::settings.delete-user-modal />
</div>
