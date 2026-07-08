<?php

namespace App\Services;

class AttendanceStateRegistry
{
    /**
     * Canonical list of all attendance states and their properties.
     */
    public static function getStates(): array
    {
        return [
            'present' => [
                'label' => 'Present',
                'color' => '#234E39',
                'bg_color' => '#D1E7DD',
                'text_color' => '#0A3622',
                'glow_color' => '0 0 6px 2px rgba(25, 135, 84, 0.4)',
                'dot_color' => '#198754',
            ],
            'absent' => [
                'label' => 'Absent',
                'color' => '#6E1A24',
                'bg_color' => '#F8D7DA',
                'text_color' => '#58151C',
                'glow_color' => '0 0 6px 2px rgba(220, 53, 69, 0.45)',
                'dot_color' => '#DC3545',
            ],
            'half' => [
                'label' => 'Half Day (Automatic)',
                'color' => '#C1652C',
                'bg_color' => '#FFE8CC',
                'text_color' => '#803D00',
                'glow_color' => '0 0 6px 2px rgba(253, 126, 20, 0.45)',
                'dot_color' => '#FD7E14',
            ],
            'planned' => [
                'label' => 'Planned Leave',
                'color' => '#234E39',
                'bg_color' => '#D1E7DD',
                'text_color' => '#0A3622',
                'glow_color' => '0 0 6px 2px rgba(25, 135, 84, 0.4)',
                'dot_color' => '#198754',
            ],
            'upa' => [
                'label' => 'Unplanned Approved (UPA)',
                'color' => '#234E39',
                'bg_color' => '#D1E7DD',
                'text_color' => '#0A3622',
                'glow_color' => '0 0 6px 2px rgba(25, 135, 84, 0.4)',
                'dot_color' => '#198754',
            ],
            'upr' => [
                'label' => 'Unplanned Rejected (UPR)',
                'color' => '#6E1A24',
                'bg_color' => '#F8D7DA',
                'text_color' => '#58151C',
                'glow_color' => '0 0 6px 2px rgba(220, 53, 69, 0.45)',
                'dot_color' => '#DC3545',
            ],
            'hdp' => [
                'label' => 'Half Day Planned (HDP)',
                'color' => '#234E39',
                'bg_color' => '#D1E7DD',
                'text_color' => '#0A3622',
                'glow_color' => '0 0 6px 2px rgba(25, 135, 84, 0.4)',
                'dot_color' => '#198754',
            ],
            'hd_upa' => [
                'label' => 'Half Day Unplanned Approved (HD-UPA)',
                'color' => '#234E39',
                'bg_color' => '#D1E7DD',
                'text_color' => '#0A3622',
                'glow_color' => '0 0 6px 2px rgba(25, 135, 84, 0.4)',
                'dot_color' => '#198754',
            ],
            'hd_upr' => [
                'label' => 'Half Day Unplanned Rejected (HD-UPR)',
                'color' => '#6E1A24',
                'bg_color' => '#F8D7DA',
                'text_color' => '#58151C',
                'glow_color' => '0 0 6px 2px rgba(220, 53, 69, 0.45)',
                'dot_color' => '#DC3545',
            ],
            'bday' => [
                'label' => 'Birthday Leave',
                'color' => '#7C5A9E',
                'bg_color' => '#F3E8FF',
                'text_color' => '#4C1D95',
                'glow_color' => '0 0 6px 2px rgba(139, 92, 246, 0.4)',
                'dot_color' => '#8B5CF6',
            ],
            'off' => [
                'label' => 'Weekly Off',
                'color' => '#6D645A',
                'bg_color' => '#E2E3E5',
                'text_color' => '#212529',
                'glow_color' => 'none',
                'dot_color' => '#6C757D',
            ],
            'future' => [
                'label' => 'Future (-)',
                'color' => '#9C9180',
                'bg_color' => '#FAF8F5',
                'text_color' => '#6D645A',
                'glow_color' => 'none',
                'dot_color' => '#9C9180',
            ],
        ];
    }

    /**
     * Map internal database/state status keys to the display keys.
     */
    public static function getDisplayStatus(string $status): string
    {
        $status = strtolower($status);
        if ($status === 'late') {
            return 'present';
        }
        if ($status === 'paid_leave') {
            return 'planned';
        }
        if ($status === 'unpaid_leave') {
            return 'upa';
        }
        if ($status === 'weekly_off') {
            return 'off';
        }
        return $status;
    }

    /**
     * Get the human readable label for a given state.
     */
    public static function getLabel(string $status): string
    {
        $displayStatus = self::getDisplayStatus($status);
        $states = self::getStates();
        return $states[$displayStatus]['label'] ?? ucfirst($status);
    }
}
