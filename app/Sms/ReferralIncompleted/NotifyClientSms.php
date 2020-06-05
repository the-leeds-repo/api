<?php

namespace App\Sms\ReferralIncompleted;

use App\Sms\Sms;

class NotifyClientSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_incompleted.notify_client.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
LOOP: Hi ((CLIENT_INITIALS)),
Your referral (ID: ((REFERRAL_ID))) has been marked as incomplete. This means the service tried to contact you but couldn't.
For details: info@connectedtogether.org.uk
The Connected Together team
EOT;
    }
}
