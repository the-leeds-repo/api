<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Sms\OtpLoginCode\UserSms;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_otp_sms_sent_to_user()
    {
        config()->set('tlr.otp_enabled', true);

        Queue::fake();

        $user = factory(User::class)->create(['password' => bcrypt('password')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Queue::assertPushed(UserSms::class, function (UserSms $sms) {
            $this->assertArrayHasKey('OTP_CODE', $sms->values);
            return true;
        });
    }
}
