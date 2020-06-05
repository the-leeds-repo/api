<?php

namespace App\Sms\ReferralCompleted;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_completed.notify_referee.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
LOOP: Hi ((REFEREE_NAME)),
The referral you made to ((SERVICE_NAME)) has been marked as complete. ID: ((REFERRAL_ID))
Your client should have been contacted by now, but if they haven't then please contact them on ((SERVICE_PHONE)) or by email at ((SERVICE_EMAIL)).
Regards,
Connected Together.
EOT;
    }
}
