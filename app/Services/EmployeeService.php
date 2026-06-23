<?php


namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function create(array $data): User
    {
        $userData = collect($data)->only([
            'employee_id', 'name', 'email', 'phone', 'password', 'role', 'status', 'joining_date', 'must_change_password', 'department_id', 'manager_id', 'admin_id'
        ])->toArray();

        $userData['password'] = Hash::make($userData['password']);
        unset($userData['password_confirmation']);

        $user = User::create($userData);

        $profileData = collect($data)->except([
            'employee_id', 'name', 'email', 'phone', 'password', 'password_confirmation', 'role', 'status', 'joining_date', 'must_change_password', 'department_id', 'manager_id', 'admin_id'
        ])->toArray();

        $profileData['user_id'] = $user->id;
        $user->employeeProfile()->create($profileData);

        if ($user->role !== 'admin') {
            \App\Services\LeaveBalanceService::initializeUser($user);
        }

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $userData = collect($data)->only([
            'employee_id', 'name', 'email', 'phone', 'password', 'role', 'status', 'joining_date', 'department_id', 'manager_id', 'admin_id'
        ])->toArray();

        if (!empty($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
            unset($userData['password_confirmation']);
        }

        $user->update($userData);

        $profileData = collect($data)->except([
            'employee_id', 'name', 'email', 'phone', 'password', 'password_confirmation', 'role', 'status', 'joining_date', 'department_id', 'manager_id', 'admin_id'
        ])->toArray();

        $user->employeeProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}