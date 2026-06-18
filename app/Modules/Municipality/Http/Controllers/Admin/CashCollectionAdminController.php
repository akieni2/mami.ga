<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Enums\CashSessionStatus;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\MunicipalPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashCollectionAdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());

        $openSessions = CashSession::query()
            ->with('agent')
            ->where('status', CashSessionStatus::Open)
            ->orderByDesc('opened_at')
            ->get();

        $collectedToday = MunicipalPayment::query()
            ->whereDate('collected_at', $date)
            ->where('status', PaymentStatus::Completed)
            ->sum('amount');

        $byAgent = MunicipalPayment::query()
            ->selectRaw('agent_id, SUM(amount) as total, COUNT(*) as count')
            ->whereDate('collected_at', $date)
            ->where('status', PaymentStatus::Completed)
            ->groupBy('agent_id')
            ->with('agent:id,name')
            ->get();

        $byDay = MunicipalPayment::query()
            ->selectRaw('DATE(collected_at) as day, SUM(amount) as total, COUNT(*) as count')
            ->where('status', PaymentStatus::Completed)
            ->where('collected_at', '>=', now()->subDays(14))
            ->groupByRaw('DATE(collected_at)')
            ->orderByDesc('day')
            ->get();

        return view('admin.municipality.collection.dashboard', [
            'date' => $date,
            'openSessions' => $openSessions,
            'collectedToday' => $collectedToday,
            'byAgent' => $byAgent,
            'byDay' => $byDay,
        ]);
    }
}
