<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $namaKlien;
    public string $item;
    public float  $jumlah;
    public float  $totalHarga;

    public function __construct(string $namaKlien, string $item, float $jumlah, float $totalHarga)
    {
        $this->namaKlien  = $namaKlien;
        $this->item       = $item;
        $this->jumlah     = $jumlah;
        $this->totalHarga = $totalHarga;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sales Invoice - {$this->item} - {$this->namaKlien}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales-invoice',
        );
    }
}
