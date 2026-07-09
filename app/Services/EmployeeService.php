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
            'employee_id', 'name', 'email', 'profile_photo_path', 'phone', 'password', 'role', 'status', 'joining_date', 'department_id', 'manager_id', 'admin_id'
        ])->toArray();

        if (!empty($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
            unset($userData['password_confirmation']);
        }

        // Hierarchy rule enforcement if role is changing from manager to something else
        if ($user->role === 'manager' && isset($userData['role']) && $userData['role'] !== 'manager') {
            $hasReports = User::where('manager_id', $user->id)->exists();
            if ($hasReports) {
                if (!empty($data['replacement_manager_id'])) {
                    User::where('manager_id', $user->id)->update(['manager_id' => $data['replacement_manager_id']]);
                } elseif (!empty($data['confirm_clear_hierarchy'])) {
                    User::where('manager_id', $user->id)->update(['manager_id' => null]);
                } else {
                    throw new \InvalidArgumentException("Resolve Direct Reports: You must assign a replacement manager or confirm clearing the hierarchy.");
                }
            }
        }

        $user->update($userData);

        $profileData = collect($data)->except([
            'employee_id', 'name', 'email', 'profile_photo_path', 'phone', 'password', 'password_confirmation', 'role', 'status', 'joining_date', 'department_id', 'manager_id', 'admin_id',
            'replacement_manager_id', 'confirm_clear_hierarchy',
            'planned_leave', 'unplanned_leave', 'paternity_leave', 'maternity_leave', 'compensatory_leave', 'carry_forward', 'utilized_leave',
            'base_salary', 'salary_effective_date', 'payroll_enabled'
        ])->toArray();

        $user->employeeProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        return $user;
    }

    public function delete(User $user): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            // Nullify relationships where this user is manager/admin
            \App\Models\User::where('manager_id', $user->id)->update(['manager_id' => null]);
            \App\Models\User::where('admin_id', $user->id)->update(['admin_id' => null]);

            // 1. ProfileCorrectionRequest
            \App\Models\ProfileCorrectionRequest::where('user_id', $user->id)->delete();

            // 2. LeaveRequestLog
            $leaveRequestIds = \App\Models\LeaveRequest::where('user_id', $user->id)->pluck('id')->toArray();
            \App\Models\LeaveRequestLog::whereIn('leave_request_id', $leaveRequestIds)->delete();
            \App\Models\LeaveRequestLog::where('user_id', $user->id)->delete();

            // 3. LeaveLedgerEntry
            \App\Models\LeaveLedgerEntry::where('user_id', $user->id)->delete();

            // 4. LeaveRequest
            \App\Models\LeaveRequest::where('user_id', $user->id)->delete();

            // 5. LeaveCredit
            \App\Models\LeaveCredit::where('user_id', $user->id)->delete();

            // 6. LeaveBalance
            \App\Models\LeaveBalance::where('user_id', $user->id)->delete();

            // 7. SalaryHistory & PayrollProfile
            $payrollProfile = \App\Models\PayrollProfile::where('user_id', $user->id)->first();
            if ($payrollProfile) {
                \App\Models\SalaryHistory::where('payroll_profile_id', $payrollProfile->id)->delete();
                $payrollProfile->delete();
            }

            // 8. EmployeeExternalIdentifier
            \App\Models\EmployeeExternalIdentifier::where('user_id', $user->id)->delete();

            // 9. Attendance
            \App\Models\Attendance::where('user_id', $user->id)->delete();

            // 10. EmployeeProfile
            \App\Models\EmployeeProfile::where('user_id', $user->id)->delete();

            // 11. User record
            $user->delete();
        });
    }
}