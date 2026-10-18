<?php

namespace App\Mail;

use App\Models\Prestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioVencimiento extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Prestamo $prestamo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recordatorio de devolución - Biblioteca Insectaria');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.recordatorio');
    }
}
