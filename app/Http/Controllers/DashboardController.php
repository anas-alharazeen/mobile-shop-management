<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    /**
     * Main operational dashboard.
     *
     * The payload is generated live on every request.
     */
    public function index(
        Request $request
    ): Response {
        $filters = $request->validate([
            'period' => [
                'nullable',
                'string',
                'in:today,yesterday,last_7_days,this_week,this_month,last_month,this_year,custom',
            ],

            'start_date' => [
                'nullable',
                'required_if:period,custom',
                'date',
                'before_or_equal:today',
            ],

            'end_date' => [
                'nullable',
                'required_if:period,custom',
                'date',
                'after_or_equal:start_date',
                'before_or_equal:today',
            ],
        ]);

        /*
         * Custom dates always win.
         */
        if (
            ! empty($filters['start_date'])
            || ! empty($filters['end_date'])
        ) {
            $filters['period'] = 'custom';
        }

        /*
         * Operational dashboard default:
         * show TODAY when no filter is supplied.
         */
        if (
            empty($filters['period'])
            && empty($filters['start_date'])
            && empty($filters['end_date'])
        ) {
            $filters['period'] = 'today';
        }

        return Inertia::render(
            'Dashboard',
            [
                'dashboardData' =>
                    $this->dashboardService
                        ->getDashboardData(
                            $filters
                        ),

                'filters' =>
                    $filters,
            ]
        );
    }
}
