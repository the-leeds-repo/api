<?php

namespace App\Emails\ScheduledReportGenerated;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.scheduled_report_generated.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

A ((REPORT_FREQUENCY)) ((REPORT_TYPE)) report has been generated.

Please login to the admin system to view the report.
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Scheduled report generated';
    }
}
