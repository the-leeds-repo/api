<?php

namespace App\Emails\ServiceCreated;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.service_created.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

A new service has been created by an organisation admin and requires a Global Administrator to review:

((SERVICE_NAME))
((ORGANISATION_NAME))
((SERVICE_INTRO))
You will need to:

Check the content entered is acceptable, descriptive, plain English, and doesn’t have any typos
Add taxonomies to the service, based on the content
Enable the service if it is acceptable
If the service is not ready to go live, please contact the user that made the request to rectify the problems.

The user that made the request was ((ORGANISATION_ADMIN_NAME)), and you can contact them via ((ORGANISATION_ADMIN_EMAIL))

To review the service, follow this link: ((SERVICE_URL))

Many thanks,

The LOOP Team
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Service Created (((SERVICE_NAME))) – Ready to review';
    }
}
