<?php

namespace App\Notifications;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Notifications
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
