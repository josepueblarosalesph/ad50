{{-- Reproduce el header/navegación del panel según el rol, para pantallas que usan el layout app
     sin ser un componente de panel (p. ej. Configuración de cuenta). --}}
@php($rolPanel = auth()->user()?->role)

@if ($rolPanel === 'postulante')
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('postulante.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi panel</a>
        <a href="{{ route('postulante.ficha') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi perfil</a>
        <a href="{{ route('postulante.busquedas') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Oportunidades</a>
    </x-slot:nav>
@elseif ($rolPanel === 'empresa')
    <x-slot:context>Empresa</x-slot:context>
    {{-- Mismo menú que el panel: sin sección activa, porque estas pantallas no son del panel. --}}
    <x-slot:nav><x-nav-empresa /></x-slot:nav>
@endif
