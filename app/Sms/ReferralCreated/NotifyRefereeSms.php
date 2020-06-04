<?php

namespace App\Sms\ReferralCreated;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_created.notify_referee.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
LOOP: You've made a connection for a client on Connected Together ((REFERRAL_ID)). The service should contact them within 10 working days. Any feedback contact info@connectedtogether.org.uk
EOT;
    }
}
