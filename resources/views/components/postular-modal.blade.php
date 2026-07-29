@props(['publicacion' => null])

{{-- Formulario de postulación. Lo usan el panel, el listado de oportunidades y el
     detalle de una publicación; la lógica vive en el trait PostulaAOfertas. --}}
<flux:modal name="postular-publicacion" class="max-w-2xl" wire:close="$set('postulandoId', null)">
    @if ($publicacion)
        <form wire:submit="postular" class="space-y-5">
            <div>
                <flux:heading size="lg">Postular a {{ $publicacion->cargo }}</flux:heading>
                <flux:text class="mt-1">{{ $publicacion->nombre_empresa }} · {{ $publicacion->comuna }}</flux:text>
            </div>

            <div class="rounded-xl bg-paper p-4">
                <p class="text-[13px] font-bold text-ink">Requisitos principales</p>
                <p class="mt-2 whitespace-pre-line text-[13px] leading-relaxed text-gray-600">{{ $publicacion->requisitos }}</p>
            </div>

            @foreach ($publicacion->preguntas ?? [] as $index => $pregunta)
                <flux:textarea wire:key="respuesta-{{ $index }}" wire:model="respuestas.{{ $index }}" :label="$pregunta.' *'" rows="3" maxlength="1000" />
            @endforeach

            @if (empty($publicacion->preguntas))
                <flux:text>Tu perfil profesional y currículum serán enviados a la empresa.</flux:text>
            @endif

            <div class="flex justify-end gap-3">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="postular">
                    <span wire:loading.remove wire:target="postular">Enviar postulación</span>
                    <span wire:loading wire:target="postular">Enviando…</span>
                </button>
            </div>
        </form>
    @endif
</flux:modal>
