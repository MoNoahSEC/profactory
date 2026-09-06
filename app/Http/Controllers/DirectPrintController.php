<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DirectPrintService;

class DirectPrintController extends Controller
{
    protected DirectPrintService $printService;

    public function __construct(DirectPrintService $printService)
    {
        $this->printService = $printService;
    }

    public function print(Request $request)
    {
        $request->validate([
            'print_url' => 'required|url'
        ]);

        $url = $request->input('print_url');

        // Simple security check to ensure it's printing from our app
        if (!str_starts_with($url, url('/'))) {
            return back()->with('error', 'رابط الطباعة غير صالح.');
        }

        $result = $this->printService->print($url);

        return back()->with($result['status'], $result['message']);
    }
}
