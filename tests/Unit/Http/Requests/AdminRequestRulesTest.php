<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Tests\TestCase;

class AdminRequestRulesTest extends TestCase
{
    public function test_package_requests_share_admin_authorization_and_rules(): void
    {
        Auth::shouldReceive('user')
            ->twice()
            ->andReturn(new User(['role' => UserRole::ADMIN]));

        foreach ([new StorePackageRequest(), new UpdatePackageRequest()] as $request) {
            $this->assertTrue($request->authorize());
            $this->assertSame([
                'monitoring_limit' => ['required', 'integer', 'min:1'],
                'price' => ['required', 'numeric', 'min:0'],
                'is_selectable' => ['boolean'],
            ], $request->rules());
        }
    }

    public function test_store_user_request_requires_admin_and_user_fields(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn(new User(['role' => UserRole::ADMIN]));

        $request = new StoreUserRequest();
        $rules = $request->rules();

        $this->assertTrue($request->authorize());
        $this->assertSame(['required', 'string', 'max:255'], $rules['name']);
        $this->assertSame(['required', 'string', 'email', 'max:255', 'unique:' . User::class], $rules['email']);
        $this->assertSame(['required', 'string', 'min:8'], $rules['password']);
        $this->assertSame('required', $rules['role'][0]);
        $this->assertInstanceOf(Enum::class, $rules['role'][1]);
    }
}
