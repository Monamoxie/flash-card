<?php

namespace Modules\Flashcard\Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Flashcard\Services\UserService;
use Modules\Flashcard\Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_create_user()
    {
        $email = fake()->email;
        $password = fake()->password;
        $name = fake()->name;

        $user = UserService::create($email, $password, $name);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->email);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertEquals($name, $user->name);
    }

    public function test_check_email_exists()
    {
        $nonExistingEmail = fake()->email;

        $user = $this->getTestUser(true);

        $this->assertTrue(UserService::checkEmailExists($user->email));
        $this->assertFalse(UserService::checkEmailExists($nonExistingEmail));
    }

    public function test_verify_credentials()
    {
        $email = fake()->email;
        $password = fake()->password;;

        $user = $this->getTestUser(false);

        $verifiedUser = UserService::verifyCredentials($user->email, $user->password);
        $invalidUser = UserService::verifyCredentials($email, $password);
        $nonExistingUser = UserService::verifyCredentials('nonexisting@example.com', 'password');

        $this->assertInstanceOf(User::class, $verifiedUser);
        $this->assertEquals($user->id, $verifiedUser->id);
        $this->assertFalse($invalidUser);
        $this->assertFalse($nonExistingUser);
    }
}
