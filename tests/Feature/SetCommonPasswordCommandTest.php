<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetCommonPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command only updates users with must_change_password = 1.
     */
    public function test_command_updates_only_users_requiring_password_change(): void
    {
        // 1. Create a user requiring password change
        $userToUpdate = User::factory()->create([
            'email' => 'update@example.com',
            'must_change_password' => true,
            'password' => Hash::make('old_password'),
        ]);

        // 2. Create an active user who has already changed their password
        $userNotToUpdate = User::factory()->create([
            'email' => 'active@example.com',
            'must_change_password' => false,
            'password' => Hash::make('personal_password'),
        ]);

        // 3. Call the Artisan command
        $newPassword = 'newCommonPassword123';
        $exitCode = Artisan::call('employees:set-common-password', [
            'password' => $newPassword,
        ]);

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert correct console output
        $output = Artisan::output();
        $this->assertStringContainsString('Updated 1 users.', $output);

        // Assert userToUpdate received new password and must_change_password is still true
        $userToUpdate->refresh();
        $this->assertTrue(Hash::check($newPassword, $userToUpdate->password));
        $this->assertTrue($userToUpdate->must_change_password);

        // Assert active user password remains unchanged
        $userNotToUpdate->refresh();
        $this->assertTrue(Hash::check('personal_password', $userNotToUpdate->password));
        $this->assertFalse($userNotToUpdate->must_change_password);
    }
}
