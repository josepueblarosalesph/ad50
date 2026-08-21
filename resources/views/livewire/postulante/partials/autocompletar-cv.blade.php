{{--
    Subida del CV que prellena la ficha.

    Va fuera del <form> del asistente en cuanto a comportamiento: el botón es
    type="button" y dispara su propia acción, porque un formulario anidado no es HTML
    válido y porque leer el CV no debe avanzar de paso.

    Si cambia el texto de consentimiento, sube Ficha::VERSION_CONSENTIMIENTO_CV: ese
    número es lo que queda registrado como "qué autorizó esta persona".
--}}
@php($nombresDeSeccion = [
    'datos' => 'Mis datos',
    'acerca' => 'Acerca de mí',
    'experiencia' => 'Experiencia',
    'educacion' => 'Formación',
    'idiomas' => 'Idiomas',
])

<div class="rounded-[14px] border border-line-2 bg-paper p-5 dark:bg-white/5">
    <div class="flex flex-wrap items-start gap-3">
        <flux:icon.sparkles class="mt-0.5 size-5 flex-none text-orange-500" />
        <div class="min-w-60 flex-1">
            <h3 class="text-[15px] font-extrabold text-ink">¿Tienes tu CV en PDF?</h3>
            <p class="mt-1 text-[13px] leading-relaxed text-gray-500">
                Lo leemos y completamos con él los campos que aún estén vacíos. Después revisas todo antes de guardar,
                y lo que ya hayas guardado no se toca. También puedes llenar tu perfil a mano y subir el CV al final.
            </p>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <label class="flex cursor-pointer items-start gap-2.5 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">
            <input type="checkbox" wire:model="aceptaProcesarCv" class="mt-0.5 size-4 flex-none rounded border-line-2 text-orange-600 focus:ring-orange-500" />
            <span>Autorizo a AD+50 a procesar este documento con el único fin de prellenar mi perfil profesional. Puedo eliminar el CV y los datos derivados cuando quiera.</span>
        </label>
        @error('aceptaProcesarCv') <p class="text-[13px] font-semibold text-red-700" role="alert">{{ $message }}</p> @enderror

        @if ($leyendoCv)
            {{-- La lectura ocurre en la cola y aquí solo se consulta si ya terminó.

                 Esto NO va dentro de una @island: acotar la respuesta a este bloque
                 parece la optimización obvia, pero cuando el resultado llega hay que
                 refrescar toda la ficha —los campos que se acaban de prellenar viven
                 fuera de aquí—, así que el poll tiene que devolver el componente entero.
                 La página pesa 44 KB, de modo que tampoco hay nada que optimizar. --}}
            <div wire:poll.3s="revisarLecturaDeCv" class="rounded-[10px] border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-950/40" role="status" aria-live="polite">
                <div class="flex items-center gap-3 text-[13px] font-semibold text-blue-800 dark:text-blue-200">
                    <flux:icon.arrow-path class="size-4 flex-none animate-spin" />
                    Leyendo tu CV… puede tardar un par de minutos.
                </div>
                <p class="mt-1 text-[12px] text-blue-700 dark:text-blue-300">Puedes seguir llenando los otros pasos mientras tanto; avisaremos aquí cuando termine.</p>
                @if ($this->segundosEsperando() > 180)
                    <p class="mt-2 text-[12px] font-semibold text-amber-700 dark:text-amber-300">Está tardando más de lo normal. Si esto no avanza, revisa que haya un worker de cola corriendo (<code>php artisan queue:work</code>).</p>
                @endif
            </div>
        @else
        <div class="flex flex-wrap items-center gap-3">
            <label for="cv-autocompletar" class="ad-btn-ghost ad-btn-sm cursor-pointer">
                <flux:icon.document-arrow-up class="size-4" />
                {{ $cvAutocompletar ? 'Elegir otro archivo' : 'Elegir mi CV en PDF' }}
                <input id="cv-autocompletar" type="file" wire:model="cvAutocompletar" accept="application/pdf,.pdf" class="sr-only" />
            </label>

            @if ($cvAutocompletar)
                <span class="min-w-0 truncate text-[13px] font-semibold text-ink">{{ $cvAutocompletar->getClientOriginalName() }}</span>
                <button type="button" wire:click="autocompletarDesdeCv" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="autocompletarDesdeCv,cvAutocompletar">
                    <span wire:loading.remove wire:target="autocompletarDesdeCv">Leer y completar</span>
                    <span wire:loading wire:target="autocompletarDesdeCv">Enviando…</span>
                </button>
            @endif

            <span wire:loading wire:target="cvAutocompletar" class="text-[13px] font-semibold text-gray-500" role="status">Cargando el archivo…</span>
        </div>
        @endif

        @error('cvAutocompletar')
            <div class="rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] font-semibold text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300" role="alert">{{ $message }}</div>
        @enderror

        @if (count($seccionesDesdeCv) > 0)
            <div class="rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] text-match" role="status">
                <b class="block">Completamos {{ implode(', ', array_map(fn ($s) => $nombresDeSeccion[$s] ?? $s, $seccionesDesdeCv)) }}.</b>
                <span class="text-gray-600">Revisa cada paso: lo que leímos puede tener errores y nada se guarda hasta que presiones el botón de cada sección.</span>
            </div>
        @endif

        @if ($revisarLoLeido)
            <div class="rounded-[10px] border border-amber-300 bg-amber-50 px-4 py-3 text-[13px] text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200" role="alert">
                <b class="block">Revisa este perfil con más atención.</b>
                El documento era difícil de leer o traía contenido inesperado, así que puede haber campos incompletos o mal interpretados.
            </div>
        @endif

        @if (count($avisosCv) > 0)
            <ul class="list-disc space-y-1 rounded-[10px] border border-line-2 bg-white/60 px-4 py-3 pl-8 text-[13px] text-gray-600 dark:bg-white/5 dark:text-gray-300">
                @foreach ($avisosCv as $aviso)
                    <li>{{ $aviso }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
