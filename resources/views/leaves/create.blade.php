<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface dark:text-on-surface leading-tight">
            {{ __('Apply for Leave') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto">
        <div class="glass-panel p-8 rounded-lg border border-outline-variant/30 space-y-6">
            <div class="flex justify-between items-center border-b border-outline-variant/30 pb-4">
                <h3 class="text-lg font-bold text-on-surface">New Leave Request</h3>
                <a href="{{ route('leaves.index') }}" class="text-on-surface-variant hover:text-primary transition text-sm flex items-center gap-1">
                    ← Back to List
                </a>
            </div>

            @if(auth()->user()->role !== 'admin')
            <div class="p-4 rounded-lg bg-surface-container-high border border-outline-variant/30 text-on-surface flex justify-between items-center">
                <span class="text-sm font-semibold">Available Leave Balance:</span>
                <span class="text-lg font-bold text-brass">{{ number_format(auth()->user()->leave_balance, 2) }} days</span>
            </div>
            @endif

            <form action="{{ route('leaves.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Leave Type (Admin Only) -->
                @if(auth()->user()->role === 'admin')
                <div>
                    <label for="leave_type" class="block text-sm font-medium text-on-surface-variant mb-1">Leave Classification</label>
                    <select name="leave_type" id="leave_type" required
                            class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2.5 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">
                        <option value="" disabled {{ old('leave_type') ? '' : 'selected' }}>Select Leave Classification</option>
                        <option value="paid_leave" {{ old('leave_type') === 'paid_leave' ? 'selected' : '' }}>Paid Leave</option>
                        <option value="unpaid_leave" {{ old('leave_type') === 'unpaid_leave' ? 'selected' : '' }}>Unpaid Leave</option>
                    </select>
                    @error('leave_type')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- Date Range Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-on-surface-variant mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required min="{{ today()->format('Y-m-d') }}" value="{{ old('start_date') }}"
                               class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2.5 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">
                        @error('start_date')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-on-surface-variant mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" required min="{{ today()->format('Y-m-d') }}" value="{{ old('end_date') }}"
                               class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2.5 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">
                        @error('end_date')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Duration Preview -->
                <div id="duration_preview" class="hidden p-4 rounded-lg bg-primary/10 border border-primary/20 text-primary flex items-center justify-between">
                    <span class="text-sm font-semibold">Leave Duration:</span>
                    <span id="duration_days" class="text-lg font-bold">0 days</span>
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-on-surface-variant mb-1">Reason for Leave</label>
                    <textarea name="reason" id="reason" rows="4" required placeholder="Provide a detailed reason for your leave request..."
                              class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4 border-t border-outline-variant/30 pt-6">
                    <a href="{{ route('leaves.index') }}" class="bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold py-3 px-6 rounded-md transition border border-outline-variant/30">
                        Cancel
                    </a>
                    <button type="submit" class="bg-primary hover:bg-primary/95 text-on-primary font-semibold py-3 px-8 rounded-md transition shadow-md">
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
