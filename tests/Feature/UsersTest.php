<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;

class UsersTest extends TestCase
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
        $response = $this->actingAs($this->user)->post('/api/v1/users', $this->data(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => [__('response.unauthenticated')]]);
        $response->assertStatus(401);
    }

    /** @test */
    public function unauthorized_user() {
        $response = $this->actingAs($this->user)->post('/api/v1/users', $this->data(), $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => [__('response.forbidden')]]);
        $response->assertStatus(403);
    }

    /** @test */
    public function a_user_can_be_added() {
        $this->authorize($this->user);

        $response = $this->actingAs($this->user)->post('/api/v1/users', $this->data(), $this->auth(), ['Content-Language' => 'tr']);
        $user = User::find(2);

        // $this->assertCount(2, User::all());
        $this->assertEquals('First Name Last Name', $user->fullName);
        $this->assertEquals('example@mocowi.com', $user->email);
        $response->assertStatus(200);
    }

    /** @test */
    public function fields_are_required() {
        $this->authorize($this->user);

        $collection = collect(['first_name' => 'Adınız', 'last_name' => 'Soyadınız', 'email' => 'E-posta Adresiniz', 'password' => 'Şifre']);

        foreach ($collection as $field => $translate) {
            $response = $this->actingAs($this->user)->post('/api/v1/users', array_merge($this->data(), [$field => '']), $this->auth(), ['Content-Language' => 'tr']);

            $response->assertJson(['data' => 'error', 'message' => [$translate.' alanı gereklidir.']]);
            $response->assertStatus(400);
            $this->assertCount(1, User::all());
        }
    }

    /** @test */
    public function email_must_be_a_valid_email() {
        $this->authorize($this->user);

        $response = $this->actingAs($this->user)->post('/api/v1/users', array_merge($this->data(), ['email' => 'Not Valid Email']), $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['E-posta Adresiniz doğru bir e-posta olmalıdır.']]);
        $response->assertStatus(400);
        $this->assertCount(1, User::all());
    }

    /** @test */
    public function a_user_can_be_retrieved() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->get('/api/v1/users/'. $user->id, $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => ['name' => $user->fullName, 'email' => $user->email]]);
        $response->assertStatus(200);
        $this->assertCount(2, User::all());
    }

    /** @test */
    public function a_user_can_be_retrieved_for_edit() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->get('/api/v1/users/'. $user->id.'/edit', $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => ['name' => $user->fullName, 'email' => $user->email]]);
        $response->assertStatus(200);
        $this->assertCount(2, User::all());
    }

    /** @test */
    public function a_user_can_be_updated() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->put('/api/v1/users/'.$user->id, $this->dataUpdate(), $this->auth(), ['Content-Language' => 'tr']);
        $user = $user->fresh();

        $response->assertStatus(200);
        $this->assertCount(2, User::all());
        $this->assertEquals('Edited First Name Edited Last Name', $user->fullName);
        $this->assertTrue(Hash::check('12345678', $user->password));
    }

    /** @test */
    public function fields_are_required_for_update() {
        $this->authorize($this->user);

        $collection = collect(['first_name' => 'Adınız', 'last_name' => 'Soyadınız']);
        $user = factory(User::class)->create();

        foreach ($collection as $field => $translate) {
            $response = $this->actingAs($this->user)->put('/api/v1/users/'.$user->id, array_merge($this->dataUpdate(), [$field => '']), $this->auth(), ['Content-Language' => 'tr']);

            $response->assertJson(['data' => 'error', 'message' => [$translate.' alanı gereklidir.']]);
            $response->assertStatus(400);
        }
    }

    /** @test */
    public function fields_should_be_string_for_update() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $collection = collect(['first_name' => 'Adınız', 'last_name' => 'Soyadınız', 'password' => 'Şifre']);

        foreach ($collection as $field => $translate) {
            $response = $this->actingAs($this->user)->put('/api/v1/users/'.$user->id, array_merge($this->dataUpdate(), [$field => 123456]), $this->auth(), ['Content-Language' => 'tr']);

            $response->assertJson(['data' => 'error', 'message' => [$translate.' karakterlerden oluşmalıdır.']]);
            $response->assertStatus(400);
        }
    }

    /** @test */
    public function fields_should_be_max_191_characters_for_update() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $collection = collect(['first_name' => 'Adınız', 'last_name' => 'Soyadınız']);
        $string = "Lorem Episom";
        while(strlen($string) < 192) {
            $string .= "Lorem Episom";
        }

        foreach ($collection as $field => $translate) {
            $response = $this->actingAs($this->user)->put('/api/v1/users/'.$user->id, array_merge($this->dataUpdate(), [$field => $string]), $this->auth(), ['Content-Language' => 'tr']);

            $response->assertJson(['data' => 'error', 'message' => [$translate.' değeri en fazla 191 karakter uzunluğunda olmalıdır.']]);
            $response->assertStatus(400);
        }
    }

    /** @test */
    public function password_min_characters_is_8_for_update() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->put('/api/v1/users/'.$user->id, array_merge($this->dataUpdate(), ['password' => '1234567']), $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Şifre değeri en az 8 karakter uzunluğunda olmalıdır.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function password_should_be_confirmed_for_update() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->put('/api/v1/users/'.$user->id, array_merge($this->dataUpdate(), ['password_confirmation' => '']), $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => 'error', 'message' => ['Şifre tekrarı eşleşmiyor.']]);
        $response->assertStatus(400);
    }

    /** @test */
    public function users_list_can_be_retrieved() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->get('/api/v1/users', $this->auth(), ['Content-Language' => 'tr']);

        $response->assertJson(['data' => [
                ['name' => $this->user->first_name." ".$this->user->last_name, 'email' => $this->user->email],
                ['name' => $user->first_name." ".$user->last_name, 'email' => $user->email]
            ]
        ]);
        $response->assertStatus(200);
        $this->assertCount(2, User::all());
    }

    /** @test */
    public function a_user_can_be_deleted() {
        $this->authorize($this->user);

        $user = factory(User::class)->create();
        $response = $this->actingAs($this->user)->delete('/api/v1/users/'. $user->id, $this->auth(), ['Content-Language' => 'tr']);

        $response->assertStatus(200);
        $this->assertCount(1, User::all());
    }

    private function data() {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'example@mocowi.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'role' => 'Super Admin'
        ];
    }

    private function dataUpdate() {
        return [
            'first_name' => 'Edited First Name',
            'last_name' => 'Edited Last Name',
            'password' => '12345678',
            'password_confirmation' => '12345678'
        ];
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
