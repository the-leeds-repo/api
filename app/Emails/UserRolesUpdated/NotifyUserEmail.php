<?php

namespace App\Emails\UserRolesUpdated;

use App\Emails\Email;

class NotifyUserEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.user_roles_updated.notify_user.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((NAME)),

Your account has had its permissions updated.

Old permissions:
((OLD_PERMISSIONS))

New permissions:
((PERMISSIONS))

If you have any questions, please contact us at info@looprepository.org.

Many thanks,

The LOOP Team
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Permissions Updated';
    }
}
