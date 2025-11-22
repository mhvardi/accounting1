<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Service\ReportService;
use App\Core\Date;

class ReportController
{
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }

        $year  = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $service = new ReportService();
        $summary = $service->getMonthlySummary($year, $month);

        View::render('reports/index', [
            'user'    => Auth::user(),
            'summary' => $summary,
            'year'    => $year,
            'month'   => $month,
            'label'   => Date::jMonthLabel($year, $month),
        ]);
    }
}
