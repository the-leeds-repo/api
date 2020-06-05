<?php

namespace App\Emails\PageFeedbackReceived;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @inheritDoc
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.page_feedback_received.notify_global_admin.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

A site feedback form has been submitted for the page:
((FEEDBACK_URL))

Here are the details:

”((FEEDBACK_CONTENT))”
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Feedback received on the site';
    }
}
