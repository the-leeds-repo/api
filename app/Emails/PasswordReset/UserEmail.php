<?php

namespace App\Emails\PasswordReset;

use App\Emails\Email;

class UserEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.password_reset.email');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

We have received a request to reset your password. Please follow this link:
((PASSWORD_RESET_LINK))

If this is not you, please ignore this message.

If you need any further help please contact info@looprepository.org
EOT;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return 'Reset forgotten password';
    }
}
