@props(['label'])

<div>
    <dt class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $label }}</dt>
    <dd class="mt-0.5 text-[14px] text-ink break-words">{{ $slot }}</dd>
</div>
