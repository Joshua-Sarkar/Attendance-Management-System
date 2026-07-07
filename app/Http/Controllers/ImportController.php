<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmployeeImportService;
use App\Models\ImportLog;
use App\Models\ImportProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function __construct(
        protected EmployeeImportService $importService
    ) {}

    /**
     * Display the employee import form and upload history.
     */
    public function showUploadForm()
    {
        if (request()->has('cancel_preview')) {
            $preview = session('import_preview');
            if ($preview && isset($preview['temp_file_path'])) {
                Storage::disk('local')->delete($preview['temp_file_path']);
            }
            session()->forget('import_preview');
            return redirect()->route('admin.import.show');
        }

        $this->importService->ensureDefaultProfilesExist();
        $profiles = ImportProfile::orderBy('name')->get();

        $history = ImportLog::with('runByUser')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.import-employees', compact('history', 'profiles'));
    }

    /**
     * Handle the file upload and generate an import preview.
     */
    public function handleUpload(Request $request)
    {
        if (!$request->has('profile_id')) {
            $request->merge(['profile_id' => 'auto']);
        }

        $request->validate([
            'file' => 'required_without:temp_file_path|file|mimes:xlsx,csv,txt|max:5120',
            'temp_file_path' => 'required_without:file|string',
            'original_filename' => 'nullable|string',
            'mode' => 'required|in:create,update',
            'update_categories' => 'required_if:mode,update|array',
            'profile_id' => 'required|string',
            'approved_mappings' => 'nullable|array',
        ]);

        $approvedMappings = $request->input('approved_mappings', []);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $tempPath = 'temp-imports/' . Str::random(40) . '.' . $file->getClientOriginalExtension();
            Storage::disk('local')->put($tempPath, file_get_contents($file->getRealPath()));
        } else {
            $tempPath = $request->input('temp_file_path');
            $filename = $request->input('original_filename', 'imported_file.xlsx');
        }
        
        $absolutePath = storage_path('app/private/' . $tempPath);
        if (!file_exists($absolutePath)) {
            $absolutePath = storage_path('app/' . $tempPath);
        }

        try {
            $updateCategories = $request->input('update_categories', []);
            $profileId = $request->input('profile_id');
            $profileIdVal = ($profileId === 'auto') ? 'auto' : (int)$profileId;

            $preview = $this->importService->preview($absolutePath, $request->mode, $updateCategories, $profileIdVal, $approvedMappings);

            // Store preview info in the session
            session()->put('import_preview', [
                'temp_file_path' => $tempPath,
                'original_filename' => $filename,
                'mode' => $request->mode,
                'update_categories' => $updateCategories,
                'matched_count' => $preview['matched_count'],
                'updated_count' => $preview['updated_count'],
                'skipped_count' => $preview['skipped_count'],
                'not_found_count' => $preview['not_found_count'],
                'suggested_matches_count' => $preview['suggested_matches_count'],
                'needs_manual_review_count' => $preview['needs_manual_review_count'],
                
                'suggested_employee_matches' => $preview['suggested_employee_matches'],
                'needs_manual_review' => $preview['needs_manual_review'],
                
                'validation_errors' => $preview['validation_errors'],
                'fields_to_update' => $preview['fields_to_update'],
                'fields_ignored' => $preview['fields_ignored'],
                'rows_count' => $preview['rows_count'],
                'spreadsheet_health' => $preview['spreadsheet_health'],
                'profile' => $preview['profile'],
                'approved_mappings' => $approvedMappings,
                'create_mappings' => $request->input('create_mappings', []),
            ]);

            return redirect()->back();

        } catch (\Throwable $e) {
            // Clean up temporary file on failure only if we just uploaded it
            if ($request->hasFile('file')) {
                Storage::disk('local')->delete($tempPath);
            }

            return redirect()->back()->withErrors([
                'file' => 'Failed to parse import file: ' . $e->getMessage(),
            ]);
        }
    }

    public function confirmImport(Request $request)
    {
        if (!$request->has('profile_id')) {
            $this->importService->ensureDefaultProfilesExist();
            $defaultProfileId = \App\Models\ImportProfile::where('is_default', true)->value('id');
            $request->merge(['profile_id' => $defaultProfileId]);
        }

        $request->validate([
            'temp_file_path' => 'required|string',
            'mode' => 'required|in:create,update',
            'update_categories' => 'nullable|array',
            'original_filename' => 'required|string',
            'profile_id' => 'required|integer',
            'approved_mappings' => 'nullable|array',
            'create_mappings' => 'nullable|array',
        ]);

        $tempPath = $request->input('temp_file_path');
        $mode = $request->input('mode');
        $updateCategories = $request->input('update_categories', []);
        $originalFilename = $request->input('original_filename');
        $profileId = (int)$request->input('profile_id');
        $approvedMappings = $request->input('approved_mappings', []);
        $createMappings = $request->input('create_mappings', []);

        $absolutePath = storage_path('app/private/' . $tempPath);
        if (!file_exists($absolutePath)) {
            $absolutePath = storage_path('app/' . $tempPath);
        }

        if (!file_exists($absolutePath)) {
            return redirect()->route('admin.import.show')->withErrors([
                'file' => 'The temporary import file has expired or is missing. Please upload the file again.',
            ]);
        }

        try {
            $result = $this->importService->import(
                $absolutePath,
                $mode,
                $updateCategories,
                auth()->id(),
                $approvedMappings,
                $profileId,
                $createMappings
            );

            // Save ImportLog with advanced stats and metadata
            ImportLog::create([
                'filename' => $originalFilename,
                'run_by_user_id' => auth()->id(),
                'rows_processed' => $result['rows_processed'],
                'created_count' => $result['created'],
                'updated_count' => $result['updated'],
                'skipped_count' => $result['skipped'],
                'error_count' => count($result['errors']),
                'duration_seconds' => $result['duration_seconds'],
                'errors' => $result['errors'],
                'metadata' => [
                    'mode' => $mode,
                    'update_categories' => $updateCategories,
                    'profile_id' => $profileId,
                ],
            ]);

            // Clean up temporary file
            Storage::disk('local')->delete($tempPath);
            session()->forget('import_preview');

            return redirect()->route('admin.import.show')->with([
                'success' => 'Employee import process completed successfully.',
                'import_results' => $result,
            ]);

        } catch (\Throwable $e) {
            Storage::disk('local')->delete($tempPath);
            session()->forget('import_preview');

            return redirect()->route('admin.import.show')->withErrors([
                'file' => 'Failed to apply import updates: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Scalable dynamic search autocomplete for manual review panel.
     */
    public function searchEmployees(Request $request)
    {
        $query = trim($request->input('q'));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('role', '!=', 'admin')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('employee_id', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'employee_id']);

        return response()->json($users);
    }
}
