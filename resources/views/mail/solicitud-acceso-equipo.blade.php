<x-mail::message>
# Solicitud de acceso al equipo

**{{ $nombreCompleto }}** intentó crear una cuenta en AD+50 con un correo del dominio de
**{{ $empresa->razon_social }}**. Como tu empresa ya tiene cuenta, no se creó una cuenta nueva:
si corresponde, agrégalo tú como usuario del equipo.

**Datos de la persona**

- Nombre: {{ $nombreCompleto }}
- Correo: {{ $email }}
@if ($telefono)
- Teléfono: {{ $telefono }}
@endif
- Empresa: {{ $empresa->razon_social }}

<x-mail::button :url="$urlEquipo">
Agregar usuario desde Equipo
</x-mail::button>

En la sección **Equipo** creas su usuario con una contraseña inicial y luego se la compartes.
Si no reconoces a esta persona, ignora este correo: sin tu acción no obtiene ningún acceso.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
