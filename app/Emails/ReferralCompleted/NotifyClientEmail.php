<?php

namespace App\Emails\ReferralCompleted;

use App\Emails\Email;

class NotifyClientEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_completed.notify_client.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

Your referral ID is ((REFERRAL_ID)).

Your connection to ((SERVICE_NAME)) has been marked as completed by the service.

This means that they have been in touch with you about accessing their service.

If you have any feedback regarding this connection or believe the service did not try to contact you, please contact the admin team via info@looprepository.org.

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
