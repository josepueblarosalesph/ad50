<?php

namespace App\Mail;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al contacto administrador de una empresa: alguien de su dominio quiso registrarse
 * y, en vez de abrir una cuenta paralela (ver Rules\EmpresaYaRegistrada), pide que lo sumen
 * al equipo. Lleva los datos que el administrador necesita para crearle el usuario desde
 * la sección Equipo, y responde directo a quien lo solicitó.
 */
class SolicitudAccesoEquipo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public string $nombre,
        public string $apellidos,
        public string $email,
        public ?string $telefono = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->nombreCompleto().' solicita acceso a la cuenta de '.$this->empresa->razon_social,
            replyTo: [new Address($this->email, $this->nombreCompleto())],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.solicitud-acceso-equipo',
            with: [
                'nombreCompleto' => $this->nombreCompleto(),
                'urlEquipo' => route('empresa.equipo'),
            ],
        );
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombre.' '.$this->apellidos);
    }
}
