<x-workflow-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
            {{ __('Apply for Leave') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center border-b border-hairline pb-4 mb-2">
            <h3 class="text-lg font-bold text-brass font-display">New Leave Request</h3>
            <a href="{{ route('leaves.index') }}" class="text-vellum-muted hover:text-brass transition text-sm flex items-center gap-1 font-semibold">
                ← Back to List
            </a>
        </div>

        @if(auth()->user()->role !== 'admin')
            <div class="p-4 rounded bg-surface border border-hairline text-vellum flex justify-between items-center">
                <span class="text-sm font-semibold">Available Leave Balance:</span>
                <span class="text-lg font-bold text-brass font-mono">{{ number_format(auth()->user()->leave_balance, 2) }} days</span>
            </div>
        @endif

        <form action="{{ route('leaves.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Leave Type -->
            <div>
                <x-input-label for="leave_type" value="Leave Type" />
                <select name="leave_type" id="leave_type" required
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2.5 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <option value="" disabled {{ old('leave_type') ? '' : 'selected' }}>Select Leave Type</option>
                    <option value="planned" {{ old('leave_type') === 'planned' ? 'selected' : '' }}>Planned Leave</option>
                    <option value="unplanned" {{ old('leave_type') === 'unplanned' ? 'selected' : '' }}>Unplanned Leave</option>
                    @if($hasBirthdayCredit ?? false)
                        <option value="complimentary" {{ old('leave_type') === 'complimentary' ? 'selected' : '' }}>
                            Birthday Leave
                        </option>
                    @endif
                </select>
                <x-input-error :messages="$errors->get('leave_type')" class="mt-1" />
            </div>

            <!-- Duration -->
            <div>
                <x-input-label for="duration" value="Duration" />
                <select name="duration" id="duration" required
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2.5 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <option value="full_day" {{ old('duration') === 'full_day' ? 'selected' : '' }}>Full Day</option>
                    <option value="half_day" {{ old('duration') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                </select>
                <x-input-error :messages="$errors->get('duration')" class="mt-1" />
            </div>

            <!-- Date Range Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Start Date -->
                <div>
                    <x-input-label for="start_date" value="Start Date" />
                    <input type="date" name="start_date" id="start_date" required value="{{ old('start_date') }}"
                           class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2.5 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                </div>

                <!-- End Date -->
                <div>
                    <x-input-label for="end_date" value="End Date" />
                    <input type="date" name="end_date" id="end_date" required value="{{ old('end_date') }}"
                           class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2.5 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                </div>
            </div>

            <!-- Duration Preview -->
            <div id="duration_preview" class="hidden p-4 rounded bg-brass/[0.08] border border-brass/30 text-brass flex items-center justify-between">
                <span class="text-sm font-semibold">Leave Duration:</span>
                <span id="duration_days" class="text-lg font-bold font-mono">0 days</span>
            </div>

            <!-- Reason -->
            <div>
                <x-input-label for="reason" value="Reason for Leave" />
                <textarea name="reason" id="reason" rows="4" required placeholder="Provide a detailed reason for your leave request..."
                          class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">{{ old('reason') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-1" />
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-4 border-t border-hairline pt-6">
                <x-secondary-button onclick="window.location.href='{{ route('leaves.index') }}'">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    Submit Application
                </x-primary-button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const durationInput = document.getElementById('duration');
            const previewContainer = document.getElementById('duration_preview');
            const durationSpan = document.getElementById('duration_days');

            function handleDurationChange() {
                if (durationInput.value === 'half_day') {
                    endDateInput.value = startDateInput.value;
                    endDateInput.style.pointerEvents = 'none';
                    endDateInput.style.opacity = '0.6';
                    endDateInput.readOnly = true;
                } else {
                    endDateInput.style.pointerEvents = 'auto';
                    endDateInput.style.opacity = '1.0';
                    endDateInput.readOnly = false;
                }
                calculateDuration();
            }

            function calculateDuration() {
                const startVal = startDateInput.value;
                const endVal = durationInput.value === 'half_day' ? startVal : endDateInput.value;

                if (durationInput.value === 'half_day') {
                    if (startVal) {
                        durationSpan.textContent = '0.5 days';
                        previewContainer.classList.remove('hidden');
                    } else {
                        previewContainer.classList.add('hidden');
                    }
                    return;
                }

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
                if (durationInput.value === 'half_day') {
                    endDateInput.value = startDateInput.value;
                } else {
                    endDateInput.min = startDateInput.value;
                }
                calculateDuration();
            });

            endDateInput.addEventListener('change', calculateDuration);
            durationInput.addEventListener('change', handleDurationChange);
        });
    </script>
</x-workflow-layout>
