<?php

namespace App\Emails\ServiceUpdatePrompt;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.service_update_prompt.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
((SERVICE_NAME)) on LOOP has not been updated in over 12 months.

View the page on LOOP:
((SERVICE_URL))

Reminders have been sent monthly to the following:
((SERVICE_ADMIN_NAMES))

Page already up to date?
Reset the clock:
((SERVICE_STILL_UP_TO_DATE_URL))

Disable page?
You can disable the page in the backend:
((SERVICE_URL))
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return '((SERVICE_NAME)) page on LOOP – Inactive for 1 year';
    }
}
