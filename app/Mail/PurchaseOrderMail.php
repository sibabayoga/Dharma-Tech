<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $komoditas;
    public float  $jumlah;
    public string $tanggalMaksimal;

    public function __construct(string $komoditas, float $jumlah)
    {
        $this->komoditas       = $komoditas;
        $this->jumlah          = $jumlah;
        $this->tanggalMaksimal = Carbon::now()->addDays(3)->translatedFormat('d F Y');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "URGENT: Purchase Order - {$this->komoditas}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order',
        );
    }
}
