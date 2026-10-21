<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar;

use Illuminate\Support\ServiceProvider;

/**
 * Loads the plugin's translations under the `alfakit-calendar::` namespace. The frontend
 * strings (`alfakit-calendar::calendar.ui`) reach the renderers through the panel's
 * `frontendTranslations()`, registered by `CalendarPlugin`. `vendor:publish
 * --tag=alfakit-calendar-translations` copies them to `lang/vendor/alfakit-calendar`.
 */
class AlfakitCalendarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'alfakit-calendar');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/resources/lang' => $this->app->langPath('vendor/alfakit-calendar'),
            ], 'alfakit-calendar-translations');
        }
    }
}
