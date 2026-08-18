<x-mail::message>
# Nuevo mensaje de contacto

**{{ $mensajeContacto->nombre }}** escribió desde la pantalla de Ayuda de AD+50.

**Datos del mensaje**

- Motivo: {{ $mensajeContacto->motivoLabel() }}
- Nombre: {{ $mensajeContacto->nombre }}
- Correo: {{ $mensajeContacto->email }}
- Recibido: {{ $mensajeContacto->created_at?->translatedFormat('j \d\e F \d\e Y, H:i') }}

**Mensaje**

> {{ $mensajeContacto->mensaje }}

<x-mail::button :url="$urlBandeja">
Responder desde la bandeja
</x-mail::button>

Puedes responder directamente a este correo: la respuesta le llega a
{{ $mensajeContacto->email }}. Eso sí, para que el mensaje quede cerrado en la bandeja hay
que marcarlo como respondido allí.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
