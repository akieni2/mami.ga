<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFinanceAccess;
use App\Modules\Municipality\Models\MunicipalFinanceJournalEntry;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceJournalController extends Controller
{
    use AuthorizesFinanceAccess;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeJournalView($request->user());

        $entries = MunicipalFinanceJournalEntry::query()
            ->with(['actor:id,name', 'mission:id,reference', 'cashSession:id,reference'])
            ->when($request->query('event_type'), fn ($q, $type) => $q->where('event_type', $type))
            ->orderByDesc('occurred_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'Journal financier municipal',
            'data' => $entries->through(fn (MunicipalFinanceJournalEntry $entry) => [
                'id' => $entry->id,
                'event_type' => $entry->event_type,
                'subject_type' => $entry->subject_type,
                'subject_id' => $entry->subject_id,
                'occurred_at' => $entry->occurred_at?->toIso8601String(),
                'actor_name' => $entry->actor?->name,
                'mission_reference' => $entry->mission?->reference,
                'cash_session_reference' => $entry->cashSession?->reference,
                'payload' => $entry->payload,
            ]),
        ]);
    }
}
