<?php

namespace Modules\Flashcard\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{

    public static function create(string $email, string $password, ?string $name = null): ?User
    {
        try {
            $user = new User;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->name = $name;
            $user->save();

            return $user;
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function checkEmailExists(string $email): bool
    {
        return User::where('email', $email)
            ->exists();
    }

    public static function verifyCredentials(string $email, string $password): bool|User
    {
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return false;
    }
}
