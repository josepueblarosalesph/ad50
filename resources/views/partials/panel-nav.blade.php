{{-- Reproduce el header/navegación del panel según el rol, para pantallas que usan el layout app
     sin ser un componente de panel (p. ej. Configuración de cuenta). --}}
@php($rolPanel = auth()->user()?->role)

@if ($rolPanel === 'postulante')
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav><x-nav-postulante /></x-slot:nav>
@elseif ($rolPanel === 'empresa')
    <x-slot:context>Empresa</x-slot:context>
    {{-- Mismo menú que el panel: sin sección activa, porque estas pantallas no son del panel. --}}
    <x-slot:nav><x-nav-empresa /></x-slot:nav>
@elseif ($rolPanel === 'admin')
    <x-slot:context>Administrador</x-slot:context>
    <x-slot:nav><x-nav-admin /></x-slot:nav>
@endif
