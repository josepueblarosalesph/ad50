@props(['user' => null])

{{--
    Botones para desatascar una cuenta que no verificó su correo. Fuente única: los
    usan Usuarios, Postulantes y Empresas, y los tres componentes incluyen el trait
    VerificaCuentas, que es de donde salen los dos métodos.

    Van como iconos porque viven en la columna de acciones de tablas anchas, donde dos
    botones con texto empujan al resto a una segunda línea. El nombre de la acción no se
    pierde: está en el tooltip para quien ve la pantalla y en el aria-label para quien
    la escucha.

    No se dibuja nada si la cuenta ya está verificada (o si no hay usuario, que en
    Postulantes puede pasar cuando la cuenta fue eliminada).
--}}

@if ($user !== null && $user->email_verified_at === null)
    <flux:tooltip content="Reenviar correo de verificación">
        <button
            type="button"
            wire:click="reenviarVerificacion({{ $user->id }})"
            wire:loading.attr="disabled"
            wire:target="reenviarVerificacion({{ $user->id }})"
            class="ad-btn-icon"
            aria-label="Reenviar el correo de verificación a {{ $user->name }}"
        ><flux:icon.envelope class="size-[18px]" /></button>
    </flux:tooltip>

    <flux:tooltip content="Marcar como verificada">
        <button
            type="button"
            wire:click="marcarVerificada({{ $user->id }})"
            wire:confirm="Vas a dar por verificado un correo sin que la persona haya hecho clic en el enlace. Hazlo solo si confirmaste su identidad por otra vía. ¿Continuar?"
            wire:loading.attr="disabled"
            wire:target="marcarVerificada({{ $user->id }})"
            class="ad-btn-icon"
            aria-label="Marcar la cuenta de {{ $user->name }} como verificada"
        ><flux:icon.check-badge class="size-[18px]" /></button>
    </flux:tooltip>
@endif
