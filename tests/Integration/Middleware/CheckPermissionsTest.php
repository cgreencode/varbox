<?php

namespace Varbox\Tests\Integration\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Varbox\Models\Permission;
use Varbox\Models\User;
use Varbox\Tests\Integration\TestCase;

class CheckPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Collection
     */
    protected $permissions;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->createUser();
        $this->createPermissions();
    }

    /** @test */
    public function it_doesnt_allow_user_without_one_permission()
    {
        Route::middleware('varbox.check.permissions:permission1')->get('/_test/check-permissions', function () {
            return 'OK';
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->user)->get('/_test/check-permissions');
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());

            return;
        }

        $this->fail('Expected Symfony\Component\HttpKernel\Exception\HttpException -> 401');
    }

    /** @test */
    public function it_doesnt_allow_user_without_multiple_permissions()
    {
        Route::middleware('varbox.check.permissions:permission1,permission2')->get('/_test/check-permissions', function () {
            return 'OK';
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->user)->get('/_test/check-permissions');
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());

            return;
        }

        $this->fail('Expected Symfony\Component\HttpKernel\Exception\HttpException -> 401');
    }

    /** @test */
    public function it_doesnt_allow_user_without_all_permissions()
    {
        $this->user->grantPermission('permission1');

        Route::middleware('varbox.check.permissions:permission1,permission2')->get('/_test/check-permissions', function () {
            return 'OK';
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->user)->get('/_test/check-permissions');
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());

            return;
        }

        $this->fail('Expected Symfony\Component\HttpKernel\Exception\HttpException -> 401');
    }

    /**
     * @return void
     */
    protected function createUser()
    {
        $this->user = User::create([
            'email' => 'test-user@mail.com',
            'password' => bcrypt('test_password'),
        ]);
    }

    /**
     * @return void
     */
    protected function createPermissions()
    {
        $this->permissions = collect();

        for ($i = 1; $i <= 3; $i++) {
            $this->permissions->push(Permission::create([
                'name' => 'permission' . $i,
                'guard' => config('auth.defaults.guard'),
            ]));
        }
    }
}
