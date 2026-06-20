<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ProfileCorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test employee can submit a correction request successfully.
     */
    public function test_employee_can_submit_correction_request(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $response = $this->actingAs($employee)->post(route('employee.corrections.store'), [
            'field' => 'Phone Number',
            'message' => 'My phone number is incorrect. It should be +91 99999 88888.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('profile_correction_requests', [
            'user_id' => $employee->id,
            'field' => 'Phone Number',
            'message' => 'My phone number is incorrect. It should be +91 99999 88888.',
            'status' => 'pending',
        ]);
    }

    /**
     * Test employee duplicate request protection.
     */
    public function test_employee_duplicate_pending_request_is_blocked(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        // Create an existing pending request
        ProfileCorrectionRequest::create([
            'user_id' => $employee->id,
            'field' => 'Phone Number',
            'message' => 'My phone number is wrong.',
            'status' => 'pending',
        ]);

        // Attempt another request on same field
        $response = $this->actingAs($employee)->post(route('employee.corrections.store'), [
            'field' => 'Phone Number',
            'message' => 'Please update it.',
        ]);

        $response->assertSessionHasErrors(['field']);
        $this->assertCount(1, ProfileCorrectionRequest::all());

        // Attempt request on a different field - should succeed
        $response2 = $this->actingAs($employee)->post(route('employee.corrections.store'), [
            'field' => 'Bank Details',
            'message' => 'My account number is incorrect.',
        ]);

        $response2->assertSessionHasNoErrors();
        $this->assertCount(2, ProfileCorrectionRequest::all());
    }

    /**
     * Test admin can view correction requests.
     */
    public function test_admin_can_view_correction_requests(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        ProfileCorrectionRequest::create([
            'user_id' => $employee->id,
            'field' => 'Designation',
            'message' => 'Incorrect designation.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.corrections.index'));

        $response->assertStatus(200);
        $response->assertSee('Incorrect designation.');
        $response->assertSee($employee->name);
    }

    /**
     * Test admin can resolve correction requests.
     */
    public function test_admin_can_resolve_correction_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $request = ProfileCorrectionRequest::create([
            'user_id' => $employee->id,
            'field' => 'Designation',
            'message' => 'Incorrect designation.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.corrections.resolve', $request), [
            'admin_note' => 'I have updated your designation to Senior Dev.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertEquals('resolved', $request->status);
        $this->assertEquals('I have updated your designation to Senior Dev.', $request->admin_note);
        $this->assertEquals($admin->id, $request->resolved_by);
        $this->assertNotNull($request->resolved_at);
    }

    /**
     * Test non-admin receives 403 on admin correction request endpoints.
     */
    public function test_non_admin_cannot_access_admin_correction_endpoints(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
        ]);

        $request = ProfileCorrectionRequest::create([
            'user_id' => $employee->id,
            'field' => 'Designation',
            'message' => 'Incorrect designation.',
            'status' => 'pending',
        ]);

        // 1. Employee trying to index requests
        $response = $this->actingAs($employee)->get(route('admin.corrections.index'));
        $response->assertStatus(403);

        // 2. Manager trying to index requests
        $response = $this->actingAs($manager)->get(route('admin.corrections.index'));
        $response->assertStatus(403);

        // 3. Employee trying to resolve request
        $response = $this->actingAs($employee)->post(route('admin.corrections.resolve', $request), [
            'admin_note' => 'resolving myself',
        ]);
        $response->assertStatus(403);

        // 4. Manager trying to resolve request
        $response = $this->actingAs($manager)->post(route('admin.corrections.resolve', $request), [
            'admin_note' => 'resolving other',
        ]);
        $response->assertStatus(403);
    }
}
