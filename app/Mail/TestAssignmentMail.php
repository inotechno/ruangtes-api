<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\Test;
use App\Models\TestAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestAssignmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Participant $participant,
        public TestAssignment $assignment,
        public Test $test
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Penugasan Tes - '.$this->test->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $testLink = rtrim($frontendUrl, '/').'/participant/test/'.$this->assignment->unique_token;

        return new Content(
            view: 'emails.test-assignment',
            with: [
                'participant' => $this->participant,
                'assignment' => $this->assignment,
                'test' => $this->test,
                'testLink' => $testLink,
                'startDate' => $this->assignment->start_date->format('d F Y H:i'),
                'endDate' => $this->assignment->end_date->format('d F Y H:i'),
                'durationMinutes' => $this->test->duration_minutes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
