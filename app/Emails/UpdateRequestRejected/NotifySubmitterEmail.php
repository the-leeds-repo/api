<?php

namespace App\Emails\UpdateRequestRejected;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.update_request_rejected.notify_submitter.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((SUBMITTER_NAME)),

Your update request for the ((RESOURCE_NAME)) (((RESOURCE_TYPE))) on ((REQUEST_DATE)) has been rejected.

If you have any questions, please contact us at info@connectedtogether.org.uk.

Many thanks,

The Connected Together team
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Update Request Rejected';
    }
}
