<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\{DatabaseMigrations, DatabaseTransactions, WithFaker, RefreshDatabase};
use App\{User, Role};
use DB;
use Illuminate\Support\Facades\Notification;

class AuthTest extends TestCase
{
    use DatabaseMigrations;
    use DatabaseTransactions;

    protected $superadmin_role;
    protected $admin_role;

    public function setUp(): void
    {
        parent::setUp();

        $this->superadmin_role = factory(Role::class)->create(['name' => 'superadmin']);
        $this->admin_role = factory(Role::class)->create(['name' => 'admin']);
    }



    /**
     * @test
     */
    public function loginSuccessful()
    {
        $password  = 'success';

        $user = factory(User::class)->create(['password' => $password]);
        $user->roles()->attach($this->admin_role->id);

        $login_response = $this->json('POST', '/api/login', ['email' => $user->email, 'password' => $password]);

        $login_response->assertStatus(201)
            ->assertJsonStructure([
                'success' =>
                'access_token',
                'token_type',
                'expires_in',
                'httpCode'
            ]);
    }


    /**
     * @test
     */
    public function loginFailed()
    {
        $password  = 'megafail';

        $user = factory(User::class)->create(['password' => $password]);
        $user->roles()->attach($this->admin_role->id);

        //Failed by incorrect password
        $login_response = $this->json('POST', '/api/login', ['email' => $user->email, 'password' => 'not_the_real_one']);
        $login_response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'The email or password is incorrect',
                'httpCode' => 401
            ]);

        //Failed by incorrect email
        $login_response = $this->json('POST', '/api/login', ['email' => 'fake@email.com', 'password' => $password]);
        $login_response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'The email or password is incorrect',
                'httpCode' => 401
            ]);
    }

    /**
     * @test
     */
    public function logoutSuccess()
    {
        $password  = 'success';

        $user = factory(User::class)->create(['password' => $password]);
        $user->roles()->attach($this->admin_role->id);

        extract(mockAuthHeaders($user)); //headers, token

        $logout_response = $this->withHeaders($headers)->json('POST', '/api/logout', []);

        $logout_response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Successfully logged out']);

        /**
         * Try a simple auth api request to confirm
         * that the logout was successfull and the old token is blacklisted.
         */
        $auth_api_response =  $this->withHeaders([
            "Authorization" => "Bearer {$token}",
        ])->json('GET', '/api/roles', []);

        $auth_api_response->assertStatus(401);
        $auth_api_response_data = get_object_vars($auth_api_response->getData());

        $this->assertIsArray($auth_api_response_data);
        $this->assertArrayHasKey('message', $auth_api_response_data);
        $this->assertEquals($auth_api_response_data['message'], 'The token has been blacklisted');
    }


    /**
     * @test
     */
    public function forgotPasswordRequest()
    {
        $password  = 'forgotten';
        $email =    "forgot@forgot.com";

        $user = factory(User::class)->create(compact('email', 'password'));
        $user->roles()->attach($this->admin_role->id);

        /**
         * Email that doesn't exist
         */
        $forgot_response = $this->json('POST', '/api/forgot', ['email' => 'noexisto@hotmail.com']);
        $forgot_response->assertStatus(501)
            ->assertJson(['success' => false, 'error' => 'Email could not be sent to this email address']);

        /**
         * Email that exists
         */
        $forgot_response = $this->json('POST', '/api/forgot', ['email' => $email]);
        $forgot_response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Password reset email sent']);

        $this->assertDatabaseHas('password_resets', compact('email'));
    }

    /**
     * @test
     */
    public function resetPassword()
    {
        $old_password  = 'forgotten';
        $email = "forgot@forgot.com";

        $user = factory(User::class)->create(['email' => $email, 'password' => $old_password]);
        $user->roles()->attach($this->admin_role->id);

        Notification::fake();

        $forgot_response = $this->json('POST', '/api/forgot', ['email' => $email]);
        $forgot_response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Password reset email sent']);

        $reset_email_token = '';

        Notification::assertSentTo(
            $user,
            \App\Notifications\MailResetPasswordNotification::class,
            function ($notification, $channels) use (&$reset_email_token) {
                $reset_email_token = $notification->token;

                return true;
            }
        );

        $this->assertDatabaseHas('password_resets', compact('email'));


        $reset_data = DB::table('password_resets')
            ->select('email', 'token')
            ->where('email', $email)->get();

        $this->assertIsObject($reset_data, 'Password reset DB::table query should return an object');
        $this->assertIsObject($reset_data[0], 'Password reset DB::table query should contain an object');
        $this->assertObjectHasAttribute('email', $reset_data[0], 'Password reset query object should have email attribute');
        $this->assertObjectHasAttribute('token', $reset_data[0], 'Password reset DB::table query should have token attribute');

        $email = $reset_data[0]->email;
        $token = $reset_email_token;
        $password = 'cambiado';
        $password_confirmation = $password;

        $reset_response = $this->json('POST', '/api/password-reset', compact('email', 'token', 'password', 'password_confirmation'));

        $reset_response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Password reset done, now you can login with this new one']);

        //Old password login
        $login_response = $this->json('POST', '/api/login', ['email' => $user->email, 'password' => $old_password]);
        $login_response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'The email or password is incorrect',
                'httpCode' => 401
            ]);

        //New Password login
        $login_response = $this->json('POST', '/api/login', ['email' => $user->email, 'password' => $password]);
        $login_response->assertStatus(201)->assertJsonStructure([
            'success' =>
            'access_token',
            'token_type',
            'expires_in',
            'httpCode'
        ]);
    }
}
