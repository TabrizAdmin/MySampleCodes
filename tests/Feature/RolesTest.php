<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passportClient();

        $this->user = factory(User::class)->create();
    }

    /** @test */
    public function unauthenticated_user() {
        $response = $this->actingAs($this->user)->get('/api/v1/roles');

        $response->assertJson(['data' => 'error', 'message' => [__('response.unauthenticated')]]);
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthorized_user() {
        $response = $this->actingAs($this->user)->get('/api/v1/roles', $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => [__('response.forbidden')]]);
        $response->assertStatus(403);
    }

    /** @test */
    public function a_role_can_be_added() {
        $this->authorize($this->user);

        $response = $this->actingAs($this->user)->post('/api/v1/roles', ['name' => 'Customer Admin'], $this->auth(), ['Content-Language' => 'tr']);
        $role = Role::orderBy('id', 'DESC')->first();

        $this->assertEquals('Customer Admin', $role->name);
        $response->assertStatus(201);
    }

    /** @test */
    public function name_is_required() {
        $this->authorize($this->user);

        $response = $this->actingAs($this->user)->post('/api/v1/roles', ['name' => ''], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan alanı gereklidir.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function name_should_be_string() {
        $this->authorize($this->user);

        $response = $this->actingAs($this->user)->post('/api/v1/roles', ['name' => 123456], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan karakterlerden oluşmalıdır.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function name_should_be_max_191_characters() {
        $this->authorize($this->user);

        $string = "Lorem Episom";
        while(strlen($string) < 192) {
            $string .= "Lorem Episom";
        }

        $response = $this->actingAs($this->user)->post('/api/v1/roles', ['name' => $string], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan değeri en fazla 191 karakter uzunluğunda olmalıdır.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function name_should_be_unique() {
        $this->authorize($this->user);

        $response = $this->actingAs($this->user)->post('/api/v1/roles', ['name' => 'Super Admin'], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan daha önceden kayıt edilmiş.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function a_role_can_be_retrieved() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Admin']);
        $response = $this->actingAs($this->user)->get('/api/v1/roles/'.$role->id.'/edit', $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => ['name' => $role->name]]);
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_cant_be_retrieved() {
        $this->authorize($this->user);

        $role = Role::find(1);
        $response = $this->actingAs($this->user)->get('/api/v1/roles/'.$role->id.'/edit', $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => [__('response.not_found')]]);
        $response->assertStatus(404);
    }

    /** @test */
    public function a_role_can_be_updated() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->put('/api/v1/roles/'.$role->id, ['name' => 'Branch Editor'], $this->auth(), ['Content-Language' => 'tr']);
        $role = $role->fresh();

        $this->assertEquals('Branch Editor', $role->name);
        $response->assertStatus(200);
    }

    /** @test */
    public function cant_change_super_admin_name() {
        $this->authorize($this->user);
        $roles = Role::where('name', 'Super Admin')->get();

        foreach ($roles as $role) {
            $response = $this->actingAs($this->user)->put('/api/v1/roles/'.$role->id, ['name' => 'Branch Editor'], $this->auth(), ['Content-Language' => 'tr']);
            $role = $role->fresh();

            $response->assertJson(['data' => 'error', 'message' => [__('response.not_found')]]);
            $response->assertStatus(404);
        }
    }

    /** @test */
    public function name_is_required_for_update() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->put('/api/v1/roles/'.$role->id, ['name' => ''], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan alanı gereklidir.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function name_should_be_string_for_update() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->put('/api/v1/roles/'.$role->id, ['name' => 123456], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan karakterlerden oluşmalıdır.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function name_should_be_max_191_characters_for_update() {
        $this->authorize($this->user);

        $string = "Lorem Episom";
        while(strlen($string) < 192) {
            $string .= "Lorem Episom";
        }

        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->put('/api/v1/roles/'.$role->id, ['name' => $string], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan değeri en fazla 191 karakter uzunluğunda olmalıdır.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function name_should_be_unique_for_update() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->put('/api/v1/roles/'.$role->id, ['name' => 'Super Admin'], $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Ünvan daha önceden kayıt edilmiş.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function a_role_can_be_deleted() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->delete('/api/v1/roles/'.$role->id, $this->auth(), ['Content-Language' => 'tr']);

        $response->assertStatus(200);
    }

    /** @test */
    public function cant_delete_super_admin() {
        $this->authorize($this->user);

        $roles = Role::where('name', 'Super Admin')->get();

        foreach ($roles as $role) {
            $response = $this->actingAs($this->user)->delete('/api/v1/roles/'.$role->id, $this->auth(), ['Content-Language' => 'tr']);
            $role = $role->fresh();

            $response->assertJson(['data' => 'error', 'message' => [__('response.not_found')]]);
            $response->assertStatus(404);
        }
    }

    /** @test */
    public function a_permission_can_be_added_to_role() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $permission = Permission::where(['name' => 'read role'])->first();
        $response = $this->actingAs($this->user)->post('/api/v1/roles/add-permission/'.$role->id.'/'.$permission->id, [], $this->auth(), ['Content-Language' => 'tr']);

        $this->assertTrue($role->hasPermissionTo($permission->name));
        $response->assertStatus(200);
    }

    /** @test */
    public function a_permission_can_be_removed_from_role() {
        $this->authorize($this->user);

        $role = Role::create(['name' => 'Customer Editor']);
        $permission = Permission::where(['name' => 'read role'])->first();
        $role->givePermissionTo($permission->name);
        $response = $this->actingAs($this->user)->delete('/api/v1/roles/remove-permission/'.$role->id.'/'.$permission->id, $this->auth(), ['Content-Language' => 'tr']);
        $role = $role->fresh();

        $this->assertFalse($role->hasPermissionTo($permission->name));
        $response->assertStatus(200);
    }

    /** @test */
    public function a_role_can_be_added_to_a_user() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $role = Role::create(['name' => 'Customer Editor']);
        $response = $this->actingAs($this->user)->post('/api/v1/users/add-role/'.$user->id.'/'.$role->id, [], $this->auth(), ['Content-Language' => 'tr']);

        $this->assertTrue($user->hasRole($role->name));
        $response->assertStatus(200);
    }

    /** @test */
    public function a_role_can_be_removed_from_a_user() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $role = Role::create(['name' => 'Customer Editor']);
        $user->assignRole($role->name);
        $response = $this->actingAs($this->user)->delete('/api/v1/users/remove-role/'.$user->id.'/'.$role->id, $this->auth(), ['Content-Language' => 'tr']);
        $user = $user->fresh();

        $this->assertFalse($user->hasRole($role->name));
        $response->assertStatus(200);
    }

    /** @test */
    public function a_permission_can_be_added_to_a_user() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $permission = Permission::where(['name' => 'read role'])->first();
        $response = $this->actingAs($this->user)->post('/api/v1/users/add-permission/'.$user->id.'/'.$permission->id, [], $this->auth(), ['Content-Language' => 'tr']);

        $this->assertTrue($user->hasPermissionTo($permission->name));
        $response->assertStatus(200);
    }

    /** @test */
    public function a_permission_can_be_removed_from_a_user() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $permission = Permission::where(['name' => 'read role'])->first();
        $user->revokePermissionTo($permission->name);
        $response = $this->actingAs($this->user)->delete('/api/v1/users/remove-permission/'.$user->id.'/'.$permission->id, $this->auth(), ['Content-Language' => 'tr']);
        $user = $user->fresh();

        $this->assertFalse($user->hasPermissionTo($permission->name));
        $response->assertStatus(200);
    }

    private function auth($user = null)
    {
        if ($user == null) {
            $user = $this->user;
        }

        Passport::actingAs($user);
        return ['Authorization' => 'Bearer ' . $user->createToken('Telgus')->accessToken];
    }

    private function authorize($user = null)
    {
        if ($user == null) {
            $user = $this->user;
        }

        $user->assignRole('Super Admin');
    }
}
