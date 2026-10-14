<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardStats;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardStats $stats): Response
    {
        return Inertia::render('Admin/Dashboard', ['stats' => $stats->all()]);
    }
}
