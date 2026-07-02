@props([
    'sidebar' => false,
])

<a {{ $attributes->class('inline-flex items-center gap-3 rounded-xl font-black text-ink') }}>
    <span class="inline-flex items-center justify-center rounded-[10px] bg-ink px-2.5 py-1.5 ring-1 ring-line-2">
        <img src="/images/ad50-logo.png" alt="" class="h-9 w-auto object-contain">
    </span>
    <span @class(['tracking-[-0.02em]', 'hidden lg:inline' => $sidebar])>AD+50</span>
</a>
