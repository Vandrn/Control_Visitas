<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmacionVisitaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $datos;

    /**
     * Create a new message instance.
     */
    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Resultado de visita - {$this->datos['tienda']} - {$this->datos['fecha_hora_fin']}")
                    ->markdown('emails.visita_confirmacion')
                    ->with([
                        'datos' => $this->datos
                    ]);
    }
}
