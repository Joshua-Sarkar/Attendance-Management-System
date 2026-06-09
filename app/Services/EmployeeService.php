<?php


namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        unset($data['password_confirmation']);

        return User::create($data);
    }
    public function update(User $user, array $data): User
{
    // Only update password if a new one was provided
    if (!empty($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    } else {
        unset($data['password']);
        unset($data['password_confirmation']);
    }

    $user->update($data);

    return $user;
}

public function delete(User $user): void
{
    $user->delete();
}
}