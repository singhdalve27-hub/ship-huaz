<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingMail extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking,
        public string $status,
        public string $recipientName
    )
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'pending'   => 'Booking Received – ' . $this->booking->booking_ref,
            'confirmed' => 'Booking Confirmed – ' . $this->booking->booking_ref,
            'cancelled' => 'Booking Cancelled – ' . $this->booking->booking_ref,
            'completed' => 'Booking Completed – ' . $this->booking->booking_ref,
        ];

         return new Envelope(
            subject: $subjects[$this->status] ?? 'Booking Update – ' . $this->booking->booking_ref,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-status',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
