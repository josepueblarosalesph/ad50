{{-- Encabezado de las pantallas de cuenta. Por defecto rotula "Configuración"; la pantalla
     "Mi cuenta" lo sobreescribe al incluirlo, porque son dos secciones distintas del menú. --}}
<div class="relative mb-6 w-full">
    <h1 class="text-[27px] font-extrabold tracking-[-0.02em]">{{ $titulo ?? 'Configuración' }}</h1>
    <p class="mb-6 mt-1.5 text-[14px] text-gray-500">{{ $bajada ?? 'Ajusta la seguridad y la apariencia de la plataforma.' }}</p>
    <flux:separator variant="subtle" />
</div>
