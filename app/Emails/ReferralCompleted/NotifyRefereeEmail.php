<?php

namespace App\Emails\ReferralCompleted;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_completed.notify_referee.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((REFEREE_NAME)),

The referral you made to ((SERVICE_NAME)) has been marked as complete. Referral ID: ((REFERRAL_ID)).

Your client should have been contacted by now, but if they haven’t then please contact them on ((SERVICE_PHONE)) or by email at ((SERVICE_EMAIL)).

If you would like to leave any feedback on the referral or get in touch with us, you can contact us at info@looprepository.org.

Many thanks,

The LOOP team
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Referral Completed';
    }
}
