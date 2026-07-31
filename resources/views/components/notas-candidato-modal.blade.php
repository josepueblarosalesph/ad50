@props([
    'notas',
    // Ficha del candidato (ancla #notas) para escribir o editar la propia nota.
    'perfilUrl' => null,
])

{{-- Vista rápida de las notas del candidato desde el listado: solo lectura. Escribir o
     editar la propia nota sigue viviendo en la ficha, que es donde está el formulario. --}}
<flux:modal name="notas-candidato" class="max-w-lg" wire:close="cerrarNotas">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">Notas del candidato</flux:heading>
            <flux:text class="mt-2">
                Lo que tú y tu equipo anotaron sobre esta persona. El postulante nunca las ve.
            </flux:text>
        </div>

        <div class="max-h-80 space-y-3 overflow-y-auto">
            @forelse ($notas as $nota)
                <div wire:key="nota-rapida-{{ $nota->id }}" class="rounded-xl border border-line-2 p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <span class="text-[13px] font-bold text-ink">{{ $nota->autorLabel() }}</span>
                        <span class="text-[12px] text-gray-400">{{ $nota->updated_at?->translatedFormat('d M Y') }}</span>
                    </div>
                    @if ($nota->esPrivada())
                        <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-paper px-2 py-0.5 text-[11px] font-bold text-gray-500"><flux:icon.lock-closed class="size-3" />Solo yo</span>
                    @endif
                    <p class="mt-1.5 whitespace-pre-line text-[13.5px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $nota->contenido }}</p>
                </div>
            @empty
                <div class="rounded-xl border border-line-2 bg-paper px-4 py-6 text-center">
                    <flux:icon.pencil-square class="mx-auto size-7 text-gray-400" />
                    <p class="mt-2 text-[13.5px] font-semibold text-ink">Aún no hay notas</p>
                    <p class="mt-1 text-[12.5px] text-gray-500">Escribe la primera desde la ficha del candidato.</p>
                </div>
            @endforelse
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            @if ($perfilUrl)
                <a wire:navigate href="{{ $perfilUrl }}" class="text-[13px] font-bold text-orange-600 underline underline-offset-2">
                    {{ $notas->isEmpty() ? 'Escribir una nota' : 'Ir a la ficha y editar la mía' }}
                </a>
            @endif
            <flux:button wire:click="cerrarNotas" variant="primary">Listo</flux:button>
        </div>
    </div>
</flux:modal>
