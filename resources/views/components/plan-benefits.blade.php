<section {{ $attributes->class(['rounded-[20px] bg-ink p-7 text-white md:p-9']) }} aria-labelledby="beneficios-planes">
    <h2 id="beneficios-planes" class="text-[22px] font-extrabold">Beneficios para tu proceso de selección</h2>
    <ul class="mt-6 grid gap-4 md:grid-cols-2">
        @foreach (['Recibe candidatos compatibles automáticamente', 'Accede a perfiles y currículums completos', 'Reduce tiempos de búsqueda', 'Encuentra talento con experiencia comprobada'] as $beneficio)
            <li wire:key="beneficio-plan-{{ $loop->index }}" class="flex gap-3 text-[14px] font-semibold text-white/90">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-orange-500 text-white"><flux:icon.check class="size-4" /></span>
                {{ $beneficio }}
            </li>
        @endforeach
    </ul>
</section>
