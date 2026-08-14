@props(['user' => null])

{{--
    Botones para desatascar una cuenta que no verificó su correo. Fuente única: los
    usan Usuarios, Postulantes y Empresas, y los tres componentes incluyen el trait
    VerificaCuentas, que es de donde salen los dos métodos.

    No se dibuja nada si la cuenta ya está verificada (o si no hay usuario, que en
    Postulantes puede pasar cuando la cuenta fue eliminada).
--}}

@if ($user !== null && $user->email_verified_at === null)
    <button
        type="button"
        wire:click="reenviarVerificacion({{ $user->id }})"
        wire:loading.attr="disabled"
        wire:target="reenviarVerificacion({{ $user->id }})"
        class="ad-btn-ghost ad-btn-sm disabled:opacity-50"
    >Reenviar correo</button>

    <button
        type="button"
        wire:click="marcarVerificada({{ $user->id }})"
        wire:confirm="Vas a dar por verificado un correo sin que la persona haya hecho clic en el enlace. Hazlo solo si confirmaste su identidad por otra vía. ¿Continuar?"
        wire:loading.attr="disabled"
        wire:target="marcarVerificada({{ $user->id }})"
        class="ad-btn-ghost ad-btn-sm disabled:opacity-50"
    >Marcar verificada</button>
@endif
