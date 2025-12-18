<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Disable Vite manifest resolution for testing.
     */
    protected function withoutVite(): void
    {
        $this->app->singleton(Vite::class, function () {
            $vite = new class extends Vite
            {
                public function __invoke($entrypoints, $buildDirectory = null): HtmlString
                {
                    return new HtmlString('');
                }

                public function asset($asset, $buildDirectory = null): string
                {
                    return "https://example.com/fake-asset/{$asset}";
                }

                public function content($asset, $buildDirectory = null): string
                {
                    return '';
                }
            };

            return $vite;
        });
    }
}
