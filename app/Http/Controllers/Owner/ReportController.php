<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Di fase 6, kita siapkan view dasarnya dulu. 
        // Export logic akan ditambahkan di Fase 7 (Finishing).
        return view('owner.reports.index');
    }
}
