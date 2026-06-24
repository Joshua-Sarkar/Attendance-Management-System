<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
            {{ __('Apply for Leave') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto">
        <div class="glass-panel p-8 rounded-lg border border-hairline space-y-6">
            <div class="flex justify-between items-center border-b border-hairline pb-4">
                <h3 class="text-lg font-bold text-brass font-display">New Leave Request</h3>
                <a href="{{ route('leaves.index') }}" class="text-vellum-muted hover:text-brass transition text-sm flex items-center gap-1">
                    ← Back to List
                </a>
            </div>

            @if(auth()->user()->role !== 'admin')
            <div class="p-4 rounded-lg bg-surface border border-hairline text-vellum flex justify-between items-center">
                <span class="text-sm font-semibold">Available Leave Balance:</span>
                <span class="text-lg font-bold text-brass font-mono">{{ number_format(auth()->user()->leave_balance, 2) }} days</span>
            </div>
            @endif

            <form action="{{ route('leaves.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Leave Type (Global) -->
                <div>
                    <label for="leave_type" class="block text-sm font-medium text-vellum-muted mb-1">Leave Type</label>
                    <select name="leave_type" id="leave_type" required
                            class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2.5 focus:ring-1 focus:ring-brass/30 focus:border-brass/50 focus:outline-none">
                        <option value="" disabled {{ old('leave_type') ? '' : 'selected' }}>Select Leave Type</option>
                        <option value="planned" {{ old('leave_type') === 'planned' ? 'selected' : '' }}>Planned Leave</option>
                        <option value="unplanned" {{ old('leave_type') === 'unplanned' ? 'selected' : '' }}>Unplanned Leave</option>
                        <option value="complimentary" {{ old('leave_type') === 'complimentary' ? 'selected' : '' }} {{ !($hasBirthdayCredit ?? false) ? 'disabled' : '' }}>
                            Birthday Leave {{ !($hasBirthdayCredit ?? false) ? '(No Birthday Credit Available)' : '' }}
                        </option>
                    </select>
                    @error('leave_type')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date Range Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-vellum-muted mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required min="{{ today()->format('Y-m-d') }}" value="{{ old('start_date') }}"
                               class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2.5 focus:ring-1 focus:ring-brass/30 focus:border-brass/50 focus:outline-none">
                        @error('start_date')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-vellum-muted mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" required min="{{ today()->format('Y-m-d') }}" value="{{ old('end_date') }}"
                               class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2.5 focus:ring-1 focus:ring-brass/30 focus:border-brass/50 focus:outline-none">
                        @error('end_date')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Duration Preview -->
                <div id="duration_preview" class="hidden p-4 rounded-lg bg-brass/[0.08] border border-brass/30 text-brass flex items-center justify-between">
                    <span class="text-sm font-semibold">Leave Duration:</span>
                    <span id="duration_days" class="text-lg font-bold font-mono">0 days</span>
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-vellum-muted mb-1">Reason for Leave</label>
                    <textarea name="reason" id="reason" rows="4" required placeholder="Provide a detailed reason for your leave request..."
                              class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 focus:ring-1 focus:ring-brass/30 focus:border-brass/50 focus:outline-none">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4 border-t border-hairline pt-6">
                    <a href="{{ route('leaves.index') }}" class="bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-3 px-6 rounded-md transition border border-hairline">
                        Cancel
                    </a>
                    <button type="submit" class="bg-brass hover:bg-brass/90 text-canvas font-semibold py-3 px-8 rounded-md transition shadow-md">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const previewContainer = document.getElementById('duration_preview');
            const durationSpan = document.getElementById('duration_days');

            function calculateDuration() {
                const startVal = startDateInput.value;
                const endVal = endDateInput.value;

                if (startVal && endVal) {
                    const start = new Date(startVal);
                    const end = new Date(endVal);

                    if (end >= start) {
                        const diffTime = Math.abs(end - start);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                        durationSpan.textContent = diffDays + ' ' + (diffDays === 1 ? 'day' : 'days');
                        previewContainer.classList.remove('hidden');
                    } else {
                        previewContainer.classList.add('hidden');
                    }
                } else {
                    previewContainer.classList.add('hidden');
                }
            }

            startDateInput.addEventListener('change', function() {
                endDateInput.min = startDateInput.value;
                calculateDuration();
            });

            endDateInput.addEventListener('change', calculateDuration);
        });
    </script>
</x-app-layout>
