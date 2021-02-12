<?php

namespace App\Providers;

use App\Events\VisitVideo;
use App\Events\UploadeNewVideo;
use Illuminate\Auth\Events\Registered;
use App\Listeners\ProcessUploadedVideo;
use App\Listeners\AddVisitVideoLogToVideoView;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        UploadeNewVideo::class=>[
            ProcessUploadedVideo::class
        ],

        VisitVideo::class=>[
            AddVisitVideoLogToVideoView::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
