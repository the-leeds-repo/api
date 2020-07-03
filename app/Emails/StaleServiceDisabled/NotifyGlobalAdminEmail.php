<?php

namespace App\Emails\StaleServiceDisabled;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.stale_service_disabled.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
((SERVICE_NAME)) on LOOP has been marked as disabled after not being updated for over a year.
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Disabled ((SERVICE_NAME)) page on LOOP;
    }
}
