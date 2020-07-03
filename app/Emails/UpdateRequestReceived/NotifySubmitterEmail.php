<?php

namespace App\Emails\UpdateRequestReceived;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.update_request_submitted.notify_submitter.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((SUBMITTER_NAME)),

Your update to ((RESOURCE_NAME)) (((RESOURCE_TYPE))) has been submitted and received. A member of the admin team will review it shortly.

If you have any questions, please get in touch at info@looprepository.org.

Many thanks,

The LOOP Team
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
