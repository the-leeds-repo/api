<?php

namespace App\Emails\UserCreated;

use App\Emails\Email;

class NotifyUserEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.user_created.notify_user.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((NAME)),

An account has been created for you using this email address. You can log in to the Connected Together admin portal at:
http://admin.connectedtogether.org.uk

Permissions:
((PERMISSIONS))

If you have any questions, you can email us at info@connectedtogether.org.uk

Many thanks,

The Connected Together team
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Account Created';
    }
}
