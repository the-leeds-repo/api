<?php

namespace Tests\Feature\Auth;

use App\Emails\PasswordReset\UserEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_email_sent_to_user()
    {
        Queue::fake();

        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        Queue::assertPushed(UserEmail::class, function (UserEmail $email) use ($user) {
            $this->assertEquals($user->email, $email->to);
            $this->assertEquals(config('tlr.notifications_template_ids.password_reset.email'), $email->templateId);
            $this->assertArrayHasKey('PASSWORD_RESET_LINK', $email->values);
            return true;
        });
    }
}
