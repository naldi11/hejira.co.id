<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionAlertController extends Controller
{
    public const SESSION_KEY = 'owner_production_alert_dismissals';

    public function dismiss(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $data = $request->validate([
            'alert_id' => ['required', 'string', 'max:40', 'regex:/\Aproduction_stock_\d{4}-\d{2}-\d{2}\z/'],
        ]);
        $key = self::SESSION_KEY.'.'.$request->user()->id;
        $dismissed = $request->session()->get($key, []);
        $dismissed[] = $data['alert_id'];
        $request->session()->put($key, array_values(array_unique($dismissed)));

        // Closing the panel only affects this login session, never stock or
        // the unresolved production warning in the database.
        return response()->json(['dismissed_alert_id' => $data['alert_id']]);
    }
}
