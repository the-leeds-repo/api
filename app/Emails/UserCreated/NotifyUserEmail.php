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

An account has been created for you using this email address. You can log in to the LOOP admin portal at:
http://admin.looprepository.org

Permissions:
((PERMISSIONS))

If you have any questions, you can email us at info@looprepository.org.

Many thanks,

The LOOP Team
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
