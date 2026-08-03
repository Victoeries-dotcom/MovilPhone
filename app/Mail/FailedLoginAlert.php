<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class FailedLoginAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Conserva solamente los datos necesarios para explicar el intento bloqueado.
     */
    public function __construct(
        public readonly string $loginEmail,
        public readonly string $ipAddress,
        public readonly Carbon $occurredAt,
    ) {}

    /** Define el asunto visible en la bandeja del usuario. */
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Alerta de seguridad: intentos fallidos en MovilPhone');
    }

    /** Conecta los datos del intento con la plantilla HTML del correo. */
    public function content(): Content
    {
        return new Content(view: 'emails.failed-login-alert');
    }
}
