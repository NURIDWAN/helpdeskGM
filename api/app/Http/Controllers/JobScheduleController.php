<?php

namespace App\Http\Controllers;

use App\Models\JobTemplate;
use App\Models\WorkReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class JobScheduleController extends Controller
{
    /**
     * Get job schedule for a specific month
     */
    public function getSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $month = $request->month;
        $year = $request->year;
        $branchId = $request->branch_id;

        $user = Auth::user();

        // Determine branch filter based on user role
        if (!$user->hasRole('superadmin') && !$user->hasRole('admin') && !$branchId) {
            $branchId = $user->branch_id;
        }

        // Get all active job templates with their branch assignments
        $query = JobTemplate::where('is_active', true)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('is_active', true);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->with([
                'branches' => function ($q) use ($branchId) {
                    $q->where('is_active', true);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                }
            ]);

        $jobTemplates = $query->get();

        // Generate schedule for the month
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $schedules = [];

        foreach ($jobTemplates as $template) {
            foreach ($template->branches as $branch) {
                $dates = $this->generateScheduleDates(
                    $template->frequency,
                    $startOfMonth,
                    $endOfMonth,
                    $branch->pivot->started_at ? Carbon::parse($branch->pivot->started_at) : null,
                    $branch->pivot->ended_at ? Carbon::parse($branch->pivot->ended_at) : null,
                    $template->schedule_details
                );

                foreach ($dates as $date) {
                    // Check if work report exists for this job on this date
                    $isCompleted = WorkReport::where('job_template_id', $template->id)
                        ->where('branch_id', $branch->id)
                        ->whereDate('created_at', $date)
                        ->exists();

                    $schedules[] = [
                        'id' => $template->id . '-' . $branch->id . '-' . $date->format('Y-m-d'),
                        'job_template_id' => $template->id,
                        'job_template_name' => $template->name,
                        'job_template_description' => $template->description,
                        'frequency' => $template->frequency,
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'date' => $date->format('Y-m-d'),
                        'is_completed' => $isCompleted,
                        'is_overdue' => !$isCompleted && $date->lt(Carbon::today()),
                    ];
                }
            }
        }

        // Sort by date
        usort($schedules, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'meta' => [
                'month' => $month,
                'year' => $year,
                'branch_id' => $branchId,
                'total' => count($schedules),
            ]
        ]);
    }

    /**
     * Generate schedule dates based on frequency
     */
    private function generateScheduleDates(
        $frequency,
        Carbon $startOfMonth,
        Carbon $endOfMonth,
        ?Carbon $startedAt,
        ?Carbon $endedAt,
        ?array $scheduleDetails = null
    ): array {
        $dates = [];
        $current = $startOfMonth->copy();

        // Adjust for started_at
        if ($startedAt && $startedAt->gt($current)) {
            $current = $startedAt->copy()->startOfDay();
        }

        switch ($frequency->value ?? $frequency) {
            case 'daily':
                while ($current->lte($endOfMonth)) {
                    if (!$endedAt || $current->lte($endedAt)) {
                        if ($current->isWeekday()) { // Only weekdays
                            $dates[] = $current->copy();
                        }
                    }
                    $current->addDay();
                }
                break;

            case 'weekly':
                $targetDays = $scheduleDetails['days'] ?? ['monday'];
                // Ensure targetDays are lowercase
                $targetDays = array_map('strtolower', $targetDays);

                $iter = $startOfMonth->copy();
                // If startedAt is after start of month, use that as start point for checking
                if ($startedAt && $startedAt->gt($iter)) {
                    $iter = $startedAt->copy()->startOfDay();
                }

                while ($iter->lte($endOfMonth)) {
                    // Check if current day name is in target days
                    if (in_array(strtolower($iter->format('l')), $targetDays)) {
                        if (!$endedAt || $iter->lte($endedAt)) {
                            $dates[] = $iter->copy();
                        }
                    }
                    $iter->addDay();
                }
                break;

            case 'monthly':
                $targetDates = $scheduleDetails['dates'] ?? [1];
                // Ensure ints
                $targetDates = array_map('intval', $targetDates);

                $iter = $startOfMonth->copy();
                if ($startedAt && $startedAt->gt($iter)) {
                    $iter = $startedAt->copy()->startOfDay();
                }

                while ($iter->lte($endOfMonth)) {
                    if (in_array($iter->day, $targetDates)) {
                        if (!$endedAt || $iter->lte($endedAt)) {
                            $dates[] = $iter->copy();
                        }
                    }
                    $iter->addDay();
                }
                break;

            case 'quarterly':
                // First day of quarter months (Jan, Apr, Jul, Oct)
                $quarterMonths = [1, 4, 7, 10];
                if (in_array($startOfMonth->month, $quarterMonths)) {
                    $firstDay = $startOfMonth->copy()->startOfMonth();
                    if (!$endedAt || $firstDay->lte($endedAt)) {
                        $dates[] = $firstDay->copy();
                    }
                }
                break;

            case 'yearly':
                // January 1st
                if ($startOfMonth->month === 1) {
                    $jan1 = Carbon::createFromDate($startOfMonth->year, 1, 1);
                    if (!$endedAt || $jan1->lte($endedAt)) {
                        $dates[] = $jan1;
                    }
                }
                break;

            case 'on_demand':
                // No scheduled dates for on-demand jobs
                break;
        }

        // Filter out dates before startOfMonth if startedAt caused iter loop to allow earlier dates? 
        // Logic above uses $iter starting max(startOfMonth, startedAt). So should be safe.
        // Wait, for daily loop I used $current which was set to startOfDay of startedAt.
        // But the while condition $current->lte($endOfMonth) handles the end. 
        // Need to make sure we don't include dates before startOfMonth if startOfMonth > startedAt.
        // My logic resets $iter to startOfMonth initially. Then checks if startedAt > iter.
        // So dates will always be >= startOfMonth. Correct.

        return $dates;
    }

    /**
     * Get today's schedule summary
     */
    public function getTodaysSummary(Request $request): JsonResponse
    {
        $branchId = $request->branch_id;
        $user = Auth::user();

        if ($user->hasRole('staff') && !$branchId) {
            $branchId = $user->branch_id;
        }

        $today = Carbon::today();

        $request->merge([
            'month' => $today->month,
            'year' => $today->year,
            'branch_id' => $branchId,
        ]);

        $scheduleResponse = $this->getSchedule($request);
        $schedules = json_decode($scheduleResponse->getContent(), true)['data'] ?? [];

        // Filter for today only
        $todaysSchedules = array_filter($schedules, function ($s) use ($today) {
            return $s['date'] === $today->format('Y-m-d');
        });

        $completed = count(array_filter($todaysSchedules, fn($s) => $s['is_completed']));
        $pending = count(array_filter($todaysSchedules, fn($s) => !$s['is_completed']));

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today->format('Y-m-d'),
                'total' => count($todaysSchedules),
                'completed' => $completed,
                'pending' => $pending,
                'schedules' => array_values($todaysSchedules),
            ]
        ]);
    }
}
