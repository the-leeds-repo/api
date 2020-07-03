<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_created.notify_referee.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello ((REFEREE_NAME)),

You’ve successfully made a referral to ((REFERRAL_SERVICE_NAME))!

They should be in touch with the client by ((REFERRAL_CONTACT_METHOD)) to speak to them about accessing the service within 10 working days.

The referral ID is ((REFERRAL_ID)). If you have any feedback regarding this connection, please contact the admin team via info@looprepository.org.

Many thanks,

The LOOP team
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Confirmation of referral';
    }
}
