@props(['label' => 'Teléfono'])

{{-- Máscara de teléfono móvil chileno: el usuario escribe 9 dígitos y se formatea como +56 9 XXXX XXXX. --}}
<flux:input
    type="tel"
    :label="$label"
    inputmode="tel"
    autocomplete="tel"
    maxlength="17"
    placeholder="+56 9 1234 5678"
    x-mask="+56 9 9999 9999"
    {{ $attributes }}
/>
