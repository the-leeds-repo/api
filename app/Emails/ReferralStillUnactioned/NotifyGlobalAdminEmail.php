<?php

namespace App\Emails\ReferralStillUnactioned;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.referral_still_unactioned.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
((REFERRAL_SERVICE_NAME)) has a referral about to expire. The details are as follows:

Referral made: ((REFERRAL_CREATED_AT))
((REFERRAL_TYPE))
Client initials: ((REFERRAL_INITIALS))
Referral ID: ((REFERRAL_ID))
Referral email address: ((SERVICE_REFERRAL_EMAIL))
Users attached to this service are as follows:

Service Worker(s):
((SERVICE_WORKERS))

Service Admin(s):
((SERVICE_ADMINS))

Organisation Admin(s):
((ORGANISATION_ADMINS))
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return '((REFERRAL_SERVICE_NAME)) has a referral about to expire';
    }
}
