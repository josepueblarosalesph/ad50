<div>

{{-- ============== HERO ============== --}}
<section class="relative overflow-hidden bg-[#1b1b1b] text-white">
    <div class="absolute inset-0 opacity-40"
         style="background: linear-gradient(120deg, rgba(58,42,30,.6), rgba(27,27,27,.9));"></div>
    <div class="absolute inset-0"
         style="background: linear-gradient(90deg, rgba(20,20,20,.92) 0%, rgba(20,20,20,.7) 42%, rgba(20,20,20,.25) 100%);"></div>

    <div class="relative max-w-[1180px] mx-auto px-11 pt-8 pb-16">
        <nav class="flex items-center justify-between py-2 mb-12">
            <div class="inline-flex items-center bg-ink rounded-[11px] px-3 py-2">
                <span class="text-white font-extrabold text-lg tracking-wide">AD+50</span>
            </div>
            <div class="hidden md:flex gap-7 text-[13.5px] font-medium text-[#dcd8d3]">
                <a href="#como" class="hover:text-white">Cómo funciona</a>
                <a href="#empresas" class="hover:text-white">Para empresas</a>
                <a href="#postulantes" class="hover:text-white">Para postulantes</a>
                <a href="#seguridad" class="hover:text-white">Seguridad</a>
            </div>
            <a href="{{ route('registro') }}" class="ad-btn-light ad-btn-sm">Ingresar</a>
        </nav>

        <div class="max-w-[760px] py-4">
            <span class="ad-eyebrow">Reclutamiento con propósito</span>
            <h1 class="text-[58px] font-extrabold leading-[1.02] tracking-[-0.02em] mt-3">
                El talento correcto, <span class="text-orange-500">filtrado</span> para cada búsqueda.
            </h1>
            <p class="text-[19px] text-[#e7e3de] mt-6 max-w-[620px] leading-[1.5]">
                La plataforma de AD Consulting que conecta a postulantes con las empresas que buscan su perfil.
                Las empresas reciben una lista corta de candidatos que <b class="text-white">cumplen sus criterios</b>
                — sin revisar uno por uno.
            </p>

            <div class="flex gap-3 mt-8 flex-wrap">
                <a href="{{ route('registro') }}?tipo=empresa" class="ad-btn-primary">
                    Soy empresa, busco talento
                    <flux:icon.arrow-right class="size-4" />
                </a>
                <a href="{{ route('registro') }}?tipo=postulante" class="ad-btn-light">
                    Soy postulante, quiero registrarme
                </a>
            </div>

            <div class="flex items-center gap-7 mt-12 pt-7 border-t border-white/15 flex-wrap">
                @foreach ([
                    ['+50', 'foco en talento con experiencia'],
                    ['1',   'ficha única, siempre actualizada'],
                    ['100%','datos bajo control de AD Consulting'],
                ] as [$big, $txt])
                    <div class="flex items-center gap-2.5 text-[12.5px] text-[#cbc7c2] font-medium">
                        <b class="text-white text-[22px] font-extrabold tracking-[-0.02em]">{{ $big }}</b>
                        <span>{!! str_replace(' ', '<br>', $txt) !!}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============== CÓMO FUNCIONA ============== --}}
<section id="como" class="max-w-[1180px] mx-auto px-11 py-20">
    <div class="max-w-[680px] mx-auto mb-10 text-center">
        <span class="ad-eyebrow">Cómo funciona</span>
        <h2 class="text-[38px] font-extrabold tracking-[-0.02em] leading-[1.06] mt-3">Un proceso. Tres pasos simples.</h2>
        <p class="text-[17px] text-gray-700 mt-4 leading-[1.55]">
            El postulante describe lo que es; la empresa describe lo que busca.
            La plataforma hace coincidir ambos de forma automática.
        </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        @foreach ([
            ['01', 'El postulante carga su ficha',  'Completa una vez sus datos, educación, industrias de interés y experiencia. La mantiene actualizada y decide su visibilidad.'],
            ['02', 'La empresa crea su búsqueda',   'Completa los criterios del perfil que busca. Lo que indica filtra; lo que deja en blanco no descarta candidatos.'],
            ['03', 'Recibe la lista filtrada',      'La plataforma entrega solo los candidatos que cumplen. Con suscripción activa, accede a su contacto directo.'],
        ] as $i => [$n, $h, $p])
            <div class="relative bg-white border border-line rounded-[14px] p-7
                        @if($i < 2) md:after:content-[''] md:after:absolute md:after:-right-4 md:after:top-1/2 md:after:size-2.5
                                    md:after:border-t-2 md:after:border-r-2 md:after:border-orange-500
                                    md:after:-translate-y-1/2 md:after:rotate-45 @endif">
                <div class="text-[40px] font-extrabold text-orange-100 leading-none tracking-[-0.02em]">{{ $n }}</div>
                <h4 class="text-[19px] font-bold mt-3.5 mb-2">{{ $h }}</h4>
                <p class="text-[14.5px] text-gray-700 leading-[1.55]">{{ $p }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ============== DOS PÚBLICOS ============== --}}
<section id="empresas" class="border-y border-line"
         style="background: linear-gradient(180deg, var(--color-orange-100), #fff);">
    <div class="max-w-[1180px] mx-auto px-11 py-20">
        <div class="text-center mb-10">
            <span class="ad-eyebrow">Para ambos lados</span>
            <h2 class="text-[38px] font-extrabold tracking-[-0.02em] mt-3">Una plataforma, dos necesidades.</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-7">
            {{-- Empresas --}}
            <div class="bg-white border border-line-2 rounded-[22px] p-10">
                <div class="size-12 rounded-[13px] bg-orange-100 text-orange-600 grid place-items-center mb-5">
                    <flux:icon.building-office-2 class="size-6" />
                </div>
                <h3 class="text-[25px] font-extrabold">Para empresas</h3>
                <p class="mt-3 text-[15px] text-gray-700 leading-[1.6]">
                    Menos tiempo de selección. En lugar de revisar decenas de CV, recibes una lista corta
                    ya filtrada por tus propios criterios.
                </p>
                <ul class="grid gap-3 my-6 list-none">
                    @foreach ([
                        'Candidatos pre-filtrados por carrera, experiencia e industria',
                        'Filtras por los criterios que completas; lo demás no descarta',
                        'Solo ves a quienes cumplen, nunca la base completa',
                    ] as $l)
                        <li class="flex gap-3 text-[14px] text-gray-700 font-medium">
                            <flux:icon.check class="size-5 text-match flex-none mt-0.5" />
                            <span>{{ $l }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('registro') }}?tipo=empresa" class="ad-btn-primary">Publicar una búsqueda</a>
            </div>

            {{-- Postulantes --}}
            <div id="postulantes" class="bg-ink text-white rounded-[22px] p-10">
                <div class="size-12 rounded-[13px] bg-orange-500/20 text-orange-500 grid place-items-center mb-5">
                    <flux:icon.user class="size-6" />
                </div>
                <h3 class="text-[25px] font-extrabold">Para postulantes</h3>
                <p class="mt-3 text-[15px] text-[#cfcbc6] leading-[1.6]">
                    Un único lugar para mostrar tu trayectoria. Cargas tu información una vez y quedas
                    visible para las empresas que buscan tu perfil.
                </p>
                <ul class="grid gap-3 my-6 list-none">
                    @foreach ([
                        'Ficha profesional siempre actualizada y bajo tu control',
                        'Defines si tu perfil está activo o inactivo',
                        'Puedes actualizar o pedir la eliminación de tus datos cuando quieras',
                    ] as $l)
                        <li class="flex gap-3 text-[14px] text-[#e3dfda] font-medium">
                            <flux:icon.check class="size-5 text-orange-500 flex-none mt-0.5" />
                            <span>{{ $l }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('registro') }}?tipo=postulante" class="ad-btn-light">Crear mi ficha</a>
            </div>
        </div>
    </div>
</section>

{{-- ============== CTA FINAL ============== --}}
<section class="relative bg-[#222] text-white overflow-hidden">
    <div class="absolute inset-0"
         style="background: linear-gradient(90deg, rgba(34,34,34,.96), rgba(34,34,34,.7));"></div>
    <div class="relative max-w-[680px] mx-auto px-11 py-16 text-center">
        <h2 class="text-[40px] font-extrabold leading-[1.06] tracking-[-0.02em]">
            El éxito está en seleccionar a las personas adecuadas para cada desafío.
        </h2>
        <div class="flex gap-3 mt-8 justify-center flex-wrap">
            <a href="#" class="ad-btn-primary">Agendar una demo</a>
            <a href="#" class="ad-btn-light">Conocer los planes</a>
        </div>
    </div>
</section>

{{-- ============== FOOTER ============== --}}
<footer id="seguridad" class="bg-ink text-[#cbc7c2]">
    <div class="max-w-[1180px] mx-auto px-11 pt-14 pb-8">
        <div class="flex justify-between gap-10 flex-wrap pb-9 border-b border-white/10">
            <div class="max-w-[330px]">
                <div class="inline-flex items-center bg-white/5 rounded-[11px] px-3 py-2 mb-4">
                    <span class="text-white font-extrabold text-lg tracking-wide">AD+50</span>
                </div>
                <p class="text-[14px] leading-[1.6]">
                    El futuro del trabajo también se construye con experiencia.
                    Una iniciativa de AD Consulting para conectar talento y organizaciones.
                </p>
                <div class="flex gap-3 mt-4">
                    @foreach (['Sence','Icontec','Mercado Público'] as $c)
                        <span class="text-[10px] font-bold tracking-wider text-[#8a8680]
                                     border border-white/15 rounded-md px-2.5 py-1.5 uppercase">{{ $c }}</span>
                    @endforeach
                </div>
            </div>
            @foreach ([
                'Plataforma' => ['Cómo funciona', 'Para empresas', 'Para postulantes', 'Planes'],
                'Compañía'   => ['AD Consulting', 'AD+50', 'Servicios', 'Contacto'],
                'Legal'      => ['Tratamiento de datos', 'Ley 21.719', 'Términos', 'Privacidad'],
            ] as $titulo => $items)
                <div>
                    <h5 class="text-white text-[13px] font-bold mb-3.5 tracking-wide">{{ $titulo }}</h5>
                    @foreach ($items as $i)
                        <a href="#" class="block text-[13.5px] text-[#b3afaa] hover:text-white mb-2">{{ $i }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="pt-6 flex justify-between text-[11.5px] text-[#86827d] flex-wrap gap-2">
            <span>© {{ date('Y') }} AD Consulting · San Martín 553, Concepción, Chile</span>
            <span>Plataforma de Perfiles y Matching de Talento</span>
        </div>
    </div>
</footer>

</div>
