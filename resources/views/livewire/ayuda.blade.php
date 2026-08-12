<div class="ad-panel">
    <x-slot:context>Ayuda</x-slot:context>
    <x-slot:nav>
        @if (auth()->user()->role === 'postulante')
            <x-nav-postulante />
        @elseif (auth()->user()->role === 'empresa')
            <x-nav-empresa />
        @endif
    </x-slot:nav>

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-[27px] font-extrabold">Ayuda y contacto</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">Resolvemos primero lo más consultado. Si no encuentras tu respuesta, escríbenos.</p>
        </div>

        {{-- Preguntas frecuentes. Acordeón servido desde el servidor: son pocas y así el
             estado abierto sobrevive a un envío del formulario. --}}
        <section class="ad-card mb-6 overflow-hidden">
            <div class="ad-card-head">
                <div class="flex items-center gap-2.5">
                    <flux:icon.question-mark-circle class="size-5 flex-none text-orange-500" />
                    <h2 class="text-[16px] font-extrabold">Preguntas frecuentes</h2>
                </div>
            </div>

            <ul class="divide-y divide-line">
                @foreach ($preguntas as $indice => $item)
                    @php($estaAbierta = $abierta === $indice)
                    <li wire:key="faq-{{ $indice }}">
                        <h3>
                            <button
                                type="button"
                                wire:click="alternar({{ $indice }})"
                                class="flex w-full items-start justify-between gap-4 px-6 py-4 text-start transition hover:bg-paper focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-orange-500 dark:hover:bg-white/5"
                                aria-expanded="{{ $estaAbierta ? 'true' : 'false' }}"
                                aria-controls="faq-respuesta-{{ $indice }}"
                            >
                                <span class="text-[14.5px] font-bold text-ink">{{ $item['pregunta'] }}</span>
                                <flux:icon.chevron-down @class(['mt-0.5 size-4 flex-none text-gray-400 transition', 'rotate-180' => $estaAbierta]) />
                            </button>
                        </h3>
                        @if ($estaAbierta)
                            <div id="faq-respuesta-{{ $indice }}" class="px-6 pb-5 -mt-1">
                                <p class="text-[13.5px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $item['respuesta'] }}</p>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Formulario de contacto --}}
        <section class="ad-card overflow-hidden">
            <div class="ad-card-head">
                <div class="flex items-center gap-2.5">
                    <flux:icon.envelope class="size-5 flex-none text-orange-500" />
                    <div>
                        <h2 class="text-[16px] font-extrabold">Escríbenos</h2>
                        <p class="mt-0.5 text-[13px] text-gray-500">Te responderemos a {{ auth()->user()->email }}.</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if (session('status'))
                    <div class="mb-5 flex items-start gap-2 rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-semibold text-match" role="status">
                        <flux:icon.check-circle class="mt-px size-4 flex-none" />{{ session('status') }}
                    </div>
                @endif

                <form wire:submit="enviar" class="space-y-5">
                    <div>
                        <label for="motivo-contacto" class="mb-1.5 block text-[13px] font-bold text-gray-700 dark:text-gray-300">Motivo</label>
                        <select
                            id="motivo-contacto"
                            wire:model="motivo"
                            class="w-full rounded-lg border border-line-2 bg-white px-3 py-2.5 text-[14px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]"
                        >
                            @foreach ($motivos as $clave => $etiqueta)
                                <option value="{{ $clave }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('motivo')<p class="mt-1.5 text-[12.5px] font-semibold text-[#A93226]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="mensaje-contacto" class="mb-1.5 block text-[13px] font-bold text-gray-700 dark:text-gray-300">Tu mensaje</label>
                        <textarea
                            id="mensaje-contacto"
                            wire:model="mensaje"
                            rows="6"
                            maxlength="3000"
                            placeholder="Cuéntanos qué necesitas. Si es un problema técnico, indícanos qué intentabas hacer y qué viste."
                            class="w-full rounded-lg border border-line-2 bg-white px-3 py-2.5 text-[14px] text-ink placeholder:text-gray-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]"
                        ></textarea>
                        @error('mensaje')<p class="mt-1.5 text-[12.5px] font-semibold text-[#A93226]">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="enviar">
                            <span wire:loading.remove wire:target="enviar">Enviar mensaje</span>
                            <span wire:loading wire:target="enviar">Enviando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
