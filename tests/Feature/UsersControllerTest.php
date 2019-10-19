<?php

namespace Tests\Feature;

use App\{Client, RoleUser, Role, User};
use Illuminate\Foundation\Testing\{DatabaseMigrations, DatabaseTransactions, WithFaker, RefreshDatabase};
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    use DatabaseMigrations;
    use DatabaseTransactions;

    public $superadmin_role;
    public $admin_role;

    public $superadmin;
    public $admin;

    public function setUp(): void
    {
        parent::setUp();
        //Creamos los roles principales de la aplicacion
        $this->superadmin_role =  factory(Role::class)->create(['name' => 'superadmin']);
        $this->admin_role =  factory(Role::class)->create(['name' => 'admin']);

        //Creamos un cliente con un usuario administrador
        $this->admin =   factory(User::class)->create();
        factory(RoleUser::class)->create(['user_id' => $this->admin->id, 'role_id' => $this->admin_role->id]);

        //El usuario superadministrador para comprobar condiciones especiales
        $this->superadmin = factory(User::class)->create();
        factory(RoleUser::class)->create(['user_id' => $this->superadmin->id, 'role_id' => $this->superadmin_role->id]);
    }



    /**
     * @test
     */
    public function createUserInvalidRequest()
    {
        $password = 'fiestita2019';

        $request_data = [
            'name' => 'El admin poderoso',
            'email'    => 'admin@admin.com',
            'password' => $password,
            'password_confirmation' => $password,
            'roles' => $this->admin_role->hash
        ];


        //Primera peticion con el email invalido
        $request_data_copy = $request_data;
        $request_data_copy['email'] = 'invalido@m';

        extract(mockAuthHeaders($this->admin)); //headers, token
        $create_user_response = $this->withHeaders($headers)->json('POST', '/api/create-user',  $request_data);

        $create_user_response->assertStatus(422);
        $response_data = $create_user_response->getData();
        $this->assertEquals($response_data->message, "The given data was invalid.");

        //Siguiente con el nombre invalido
        $request_data_copy = $request_data;
        $request_data_copy['name'] = "/94k39+11f[[]";

        $create_user_response = $this->withHeaders($headers)->json('POST', '/api/create-user',  $request_data_copy);

        $create_user_response->assertStatus(422);
        $response_data = $create_user_response->getData();
        $this->assertEquals($response_data->message, "The given data was invalid.");

        //Siguiente con las contraseñas no coincidentes
        $request_data_copy = $request_data;
        $request_data_copy['password_confirmation'] = "fiestita2018";

        $create_user_response = $this->withHeaders($headers)->json('POST', '/api/create-user',  $request_data_copy);

        $create_user_response->assertStatus(422);
        $response_data = $create_user_response->getData();
        $this->assertEquals($response_data->message, "The given data was invalid.");


        //Siguiente con el campo de roles vacio
        $request_data_copy = $request_data;
        $request_data_copy['roles'] = null;

        $create_user_response = $this->withHeaders($headers)->json('POST', '/api/create-user',  $request_data_copy);

        $create_user_response->assertStatus(422);
        $response_data = $create_user_response->getData();
        $this->assertEquals($response_data->message, "The given data was invalid.");
    }

    /**
     * Petición valida para la creacion de un nuevo usuario
     * @test
     */
    public function createUserValidRequest()
    {
        $password = 'fiestita2019';

        $request_data = [
            'name' => 'El admin poderoso',
            'email'    => 'admin@admin.com',
            'password' => $password,
            'password_confirmation' => $password,
            'roles' => [
                ['label' => 'admin', 'value' => $this->admin_role->hash],
                ['label' => 'superadmin', 'value' => $this->superadmin_role->hash],

            ]
        ];

        extract(mockAuthHeaders($this->admin)); //headers, token

        $create_user_response = $this->withHeaders($headers)
            ->json('POST', '/api/create-user',  $request_data);

        $create_user_response->assertStatus(201)->assertJsonStructure([
            'success',
            'message',
            'user'
        ]);

        $response_data = $create_user_response->getData();
        $user_created = $response_data->user;
        $this->assertEquals($response_data->message, "Usuario creado con éxito");
        $this->assertEquals($user_created->name, $request_data['name'], 'El usuario creado no tiene el nombre pasado por parámetros');
        $this->assertEquals($user_created->email, $request_data['email'], 'El usuario creado no tiene el email pasado por parametros');
        $this->assertCount(2, $user_created->roles, 'El usuario creado no tiene todos los roles correctos asignados');
        $this->assertObjectNotHasAttribute('password', $user_created, 'El usuario devuelto viene con la contraseña');
    }

    /**
     * Diversas situaciones donde la utilizacion de este endpoint
     * no está autorizada o es invalida.
     * @test
     */

    public function retrieveRolesInvalidRequest()
    {
        //Metodo POST no permitido
        extract(mockAuthHeaders($this->admin)); //headers, token

        $retrieve_roles_response = $this->withHeaders($headers)
            ->json('POST', '/api/roles');

        $response_data = $retrieve_roles_response->getData();
        $this->assertEquals($response_data->message, "The POST method is not supported for this route. Supported methods: GET, HEAD.");

        //Metodo PUT no permitido
        $retrieve_roles_response = $this->withHeaders($headers)
            ->json('PUT', '/api/roles');

        $response_data = $retrieve_roles_response->getData();
        $this->assertEquals($response_data->message, "The PUT method is not supported for this route. Supported methods: GET, HEAD.");

        //Metodo DELETE NO PERMITIDO
        $retrieve_roles_response = $this->withHeaders($headers)
            ->json('DELETE', '/api/roles');

        $response_data = $retrieve_roles_response->getData();
        $this->assertEquals($response_data->message, "The DELETE method is not supported for this route. Supported methods: GET, HEAD.");
    }

    /**
     * Traer los roles de base de datos excepto el de superadmin
     * @test
     */

    public function retrieveRolesValidRequest()
    {
        extract(mockAuthHeaders($this->admin)); //headers, token

        $retrieve_roles_response = $this->withHeaders($headers)
            ->json('GET', '/api/roles');
        $retrieve_roles_response->assertStatus(201)
            ->assertJsonStructure(['success', 'roles']);

        $response_data = $retrieve_roles_response->getData();

        $this->assertTrue($response_data->success);

        foreach ($response_data->roles as $role) {
            $this->assertNotEquals($role->name, 'superadmin');
        }
    }
}
