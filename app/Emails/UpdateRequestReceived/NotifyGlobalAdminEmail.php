<?php

namespace App\Emails\UpdateRequestReceived;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.update_request_submitted.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

An update request has been created for the ((RESOURCE_TYPE)) with the ID: ((RESOURCE_ID)).

Please review the request below before approving/rejecting:
((REQUEST_URL))

Regards,

Connected Together.
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Update Request Submitted';
    }
}
