<?php

namespace App\Sms\OtpLoginCode;

use App\Sms\Sms;

class UserSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('tlr.notifications_template_ids.otp_login_code.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
LOOP: ((OTP_CODE)) is your authentication code
EOT;
    }
}
