<?php

namespace Varbox\Tests\Integration\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Varbox\Models\Config;
use Varbox\Tests\Integration\TestCase;

class OverrideConfigsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->createConfigs();

        Route::middleware('varbox.override.configs')->get('/_test/override-configs', function () {
            return implode(' --- ', [
                config('app.name'), config('auth.guards.default'), config('logging.default')
            ]);
        });
    }

    /** @test */
    public function it_overrides_the_config_values_for_the_allowed_keys()
    {
        $this->app['config']->set('varbox.varbox-config.keys', [
            'app.name', 'auth.guards.default'
        ]);

        $response = $this->get('/_test/override-configs');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Test App Name', $response->getContent());
        $this->assertStringContainsString('test_guard', $response->getContent());
        $this->assertStringNotContainsString('test_log', $response->getContent());
    }

    /**
     * @return void
     */
    protected function createConfigs()
    {
        Config::create([
            'key' => 'app.name',
            'value' => 'Test App Name',
        ]);

        Config::create([
            'key' => 'auth.guards.default',
            'value' => 'test_guard',
        ]);

        Config::create([
            'key' => 'logging.default',
            'value' => 'test_log',
        ]);
    }
}
