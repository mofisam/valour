<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReportService;

class ReceivablesController extends Controller
{
    public function index(): void
    {
        $this->view('receivables/index', ['title' => 'Receivables'] + ReportService::receivables());
    }
}
