<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyClientEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_created.notify_client.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

You’ve successfully connected to ((REFERRAL_SERVICE_NAME))!

They should be in touch with you via ((REFERRAL_CONTACT_METHOD)) within 10 working days.

Your referral ID is ((REFERRAL_ID)).

If you have any feedback regarding this connection, or have not heard back within 10 working days, please contact the admin team via info@connectedtogether.org.uk.

Alternatively, you can complete the referral feedback form:
https://docs.google.com/forms/d/e/1FAIpQLSe38Oe0vsqLRQbcBjYrGzMooBJKkYqFWAlHy4dcgwJnMFg9dQ/viewform?usp=pp_url&entry.400427747=((REFERRAL_ID)).

Many thanks,

The Connected Together team
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
