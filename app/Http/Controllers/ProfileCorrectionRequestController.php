<?php

namespace App\Http\Controllers;

use App\Models\ProfileCorrectionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileCorrectionRequestController extends Controller
{
    /**
     * Store a new profile correction request.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // 1. Employee rule checking
        if (!$user || $user->role !== 'employee') {
            abort(403, 'Only employees can submit correction requests.');
        }

        // 2. Validation
        $allowedFields = [
            'Phone Number',
            'Personal Email',
            'Official Email',
            'Department',
            'Designation',
            'Reporting Manager',
            'Joining Date',
            'Address',
            'Bank Details',
            'Emergency Contact',
            'Other'
        ];

        $request->validate([
            'field' => ['required', 'string', 'in:' . implode(',', $allowedFields)],
            'message' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $field = $request->input('field');

        // 3. Duplicate Request Protection
        $exists = ProfileCorrectionRequest::where('user_id', $user->id)
            ->where('field', $field)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'field' => "A correction request for the field '{$field}' is already awaiting review."
            ]);
        }

        // 4. Create request
        ProfileCorrectionRequest::create([
            'user_id' => $user->id,
            'field' => $field,
            'message' => $request->input('message'),
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Your profile correction request has been submitted successfully for admin review.');
    }

    /**
     * Display correction requests for Admin.
     */
    public function adminIndex(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Fetch requests, pending first, then resolved, latest first
        $requests = ProfileCorrectionRequest::with(['requester.department', 'resolver'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return view('admin.correction-requests.index', compact('requests'));
    }

    /**
     * Resolve a correction request.
     */
    public function adminResolve(Request $request, ProfileCorrectionRequest $correctionRequest)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $correctionRequest->update([
            'status' => 'resolved',
            'admin_note' => $request->input('admin_note'),
            'resolved_by' => auth()->user()->id,
            'resolved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Correction request marked as resolved.');
    }

    /**
     * Admin adds a profile correction record directly.
     */
    public function adminStore(Request $request, User $user)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'field' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:1000'], // Every correction requires a reason/message
            'status' => ['required', 'in:pending,resolved'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $request->input('status');
        $resolvedBy = ($status === 'resolved') ? auth()->user()->id : null;
        $resolvedAt = ($status === 'resolved') ? now() : null;

        ProfileCorrectionRequest::create([
            'user_id' => $user->id,
            'field' => $request->input('field'),
            'message' => $request->input('message'),
            'status' => $status,
            'admin_note' => $request->input('admin_note'),
            'resolved_by' => $resolvedBy,
            'resolved_at' => $resolvedAt,
        ]);

        return redirect()->back()->with('success', 'Correction request added successfully.');
    }

    /**
     * Admin updates a profile correction record.
     */
    public function adminUpdate(Request $request, ProfileCorrectionRequest $correctionRequest)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'field' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:1000'],
            'status' => ['required', 'in:pending,resolved'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $request->input('status');
        $resolvedBy = ($status === 'resolved') ? auth()->user()->id : null;
        $resolvedAt = ($status === 'resolved') ? now() : null;

        $correctionRequest->update([
            'field' => $request->input('field'),
            'message' => $request->input('message'),
            'status' => $status,
            'admin_note' => $request->input('admin_note'),
            'resolved_by' => $resolvedBy,
            'resolved_at' => $resolvedAt,
        ]);

        return redirect()->back()->with('success', 'Correction request updated successfully.');
    }

    /**
     * Admin deletes a profile correction record.
     */
    public function adminDestroy(ProfileCorrectionRequest $correctionRequest)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $correctionRequest->delete();

        return redirect()->back()->with('success', 'Correction request deleted.');
    }
}
