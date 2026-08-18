<?php

namespace App\Mail;

use App\Models\MensajeContacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de que alguien escribió desde el formulario de Ayuda.
 *
 * El mensaje ya quedó guardado en la bandeja de la administración antes de enviarse este
 * correo (ver Ayuda::enviar); esto es un aviso para no depender de que alguien entre a
 * mirar. A dónde llega lo decide el motivo: soporte técnico va a la casilla de soporte y
 * el resto a todas las cuentas de administración.
 *
 * El `replyTo` apunta a quien escribió, así que responder desde el cliente de correo
 * contesta a la persona y no a la casilla del sistema. Ojo: responder por ahí no marca el
 * mensaje como respondido en la bandeja, eso se sigue haciendo en Admin\Mensajes.
 */
class MensajeContactoRecibido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MensajeContacto $mensajeContacto) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.$this->mensajeContacto->motivoLabel().'] '.$this->mensajeContacto->nombre.' escribió desde AD+50',
            replyTo: [new Address($this->mensajeContacto->email, $this->mensajeContacto->nombre)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mensaje-contacto-recibido',
            with: [
                'mensajeContacto' => $this->mensajeContacto,
                'urlBandeja' => route('admin.mensajes'),
            ],
        );
    }
}
