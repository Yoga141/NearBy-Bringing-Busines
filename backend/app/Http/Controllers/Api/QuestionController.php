<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    private const LABEL_TO_VALUE = [
        'Baru' => 'baru',
        'Dijawab' => 'dijawab',
        'Ditutup' => 'ditutup',
    ];

    /** Ask a question from the help widget. Open to guests, like bug reports. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
            'contact' => ['nullable', 'string', 'max:160'],
        ]);

        // Public route, so auth:sanctum isn't in the middleware stack -
        // resolve the token directly to attach a user when one is signed in.
        $question = Question::create([
            'user_id' => $request->user('sanctum')?->id,
            'name' => $data['name'] ?? null,
            'contact' => $data['contact'] ?? null,
            'text' => $data['text'],
            'status' => 'baru',
        ]);

        return new QuestionResource($question);
    }

    /** Admin: the triage queue, newest first. */
    public function index()
    {
        return QuestionResource::collection(Question::with('user')->latest()->get());
    }

    /** Admin: write or update the answer. Saving an answer marks it Dijawab. */
    public function answer(Request $request, Question $question)
    {
        $data = $request->validate([
            'answer' => ['required', 'string', 'max:4000'],
        ]);

        $question->update(['answer' => $data['answer'], 'status' => 'dijawab']);

        return new QuestionResource($question->load('user'));
    }

    /** Admin: move a question through Baru → Dijawab → Ditutup. */
    public function updateStatus(Request $request, Question $question)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::LABEL_TO_VALUE))],
        ]);

        $question->update(['status' => self::LABEL_TO_VALUE[$data['status']]]);

        return new QuestionResource($question->load('user'));
    }
}
