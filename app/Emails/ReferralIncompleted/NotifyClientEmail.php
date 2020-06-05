<?php

namespace App\Emails\ReferralIncompleted;

use App\Emails\Email;

class NotifyClientEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_incompleted.notify_client.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

Referral ID: ((REFERRAL_ID))

Your referral to ((SERVICE_NAME)) has been marked as incomplete with the following message:

“((REFERRAL_STATUS))“.

If you believe the service did not try to contact you, or you have any other feedback regarding the connection, please contact us at info@connectedtogether.org.uk.

Alternatively, you can complete our feedback form:
https://docs.google.com/forms/d/e/1FAIpQLSe38Oe0vsqLRQbcBjYrGzMooBJKkYqFWAlHy4dcgwJnMFg9dQ/viewform?usp=pp_url&entry.400427747=((REFERRAL_ID))

Many thanks,

The Connected Together team.
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Referral Incompleted';
    }
}
