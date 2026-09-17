<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProblemReportResource;
use App\Models\ProblemReport;
use Illuminate\Http\Request;

class ProblemReportController extends Controller
{
    /** Submit a bug/issue report from the help widget. Open to guests too. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', 'string', 'max:80'],
            'text' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        // This endpoint is public (the help widget works for guests too), so
        // auth:sanctum isn't in the route's middleware — resolve the token
        // directly against the sanctum guard to attach a user when present.
        $report = ProblemReport::create([
            'user_id' => $request->user('sanctum')?->id,
            'kind' => $data['kind'],
            'text' => $data['text'],
            'name' => $data['name'] ?? null,
            'status' => 'baru',
        ]);

        return new ProblemReportResource($report);
    }
}
