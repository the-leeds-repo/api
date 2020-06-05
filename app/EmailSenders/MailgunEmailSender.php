<?php

namespace App\EmailSenders;

use App\Contracts\EmailSender;
use App\Contracts\VariableSubstituter;
use App\Emails\Email;
use Mailgun\Mailgun;

class MailgunEmailSender implements EmailSender
{
    /**
     * @inheritDoc
     */
    public function send(Email $email)
    {
        /** @var \App\Contracts\VariableSubstituter $variableSubstituter */
        $variableSubstituter = resolve(VariableSubstituter::class);

        $subject = $variableSubstituter->substitute(
            $email->getSubject(),
            $email->values
        );

        $content = $variableSubstituter->substitute(
            $email->getContent(),
            $email->values
        );

        /** @var \Mailgun\Mailgun $client */
        $client = resolve(Mailgun::class);

        $fromName = config('mail.from.name');
        $fromAddress = config('mail.from.address');

        $response = $client
            ->messages()
            ->send(config('services.mailgun.domain'), [
                'from' => "{$fromName} <{$fromAddress}>",
                'to' => $email->to,
                'subject' => $subject,
                'text' => $content,
            ]);

        $email->notification->update(['message' => $content]);

        if (config('app.debug')) {
            logger()->debug('Email sent', (array)$response);
        }
    }
}
