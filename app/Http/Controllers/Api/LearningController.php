<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWord;
use App\Models\Word;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LearningController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranslateWord(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('user_word.tw', 0)
            ->orderBy('user_word.word_id', 'DESC')
            ->select(
                'word.name',
                'word.type',
                'user_word.*',
                'word.id',
                'word.translate',
                'word.prsi',
                'word.prsh',
                'word.pas',
                'word.pas2',
                'word.pasp',
                'word.pasp2',
                'word.ing',
                'word.plural',
            )
            ->limit(6)
            ->get();
        foreach ($words as &$word) {
            $toTranslate = Word::select('name')
                ->where('type', $word->type)
                ->limit(5)
                ->inRandomOrder()
                ->pluck('name')
                ->toArray();
            if (!in_array($word->name, $toTranslate)) {
                $toTranslate[] = $word->name;
            }
            shuffle($toTranslate);
            $word->checkName = $toTranslate;
        }
        return response()->json(['status' => 'success', 'data' => $words]);
    }

    /**
     * @return JsonResponse
     */
    public function getWordTranslate(): JsonResponse
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('user_word.wt', 0)
            ->orderBy('user_word.word_id', 'DESC')
            ->select(
                'word.name',
                'user_word.*',
                'word.id',
                'word.translate',
                'word.description',
                'word.type',
                'word.prsi',
                'word.prsh',
                'word.pas',
                'word.pas2',
                'word.pasp',
                'word.pasp2',
                'word.ing',
                'word.plural',
            )
            ->limit(6)
            ->get();
        foreach ($words as &$word) {
            $toTranslate = Word::select('translate')
                ->where('type', $word->type)
                ->limit(5)
                ->inRandomOrder()
                ->pluck('translate')
                ->toArray();
            if (!in_array($word->translate, $toTranslate)) {
                $toTranslate[] = $word->translate;
            }
            shuffle($toTranslate);
            $word->checkTranslate = $toTranslate;
        }

        return response()->json(['status' => 'success', 'data' => $words]);
    }

    public function getWordAudio(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('user_word.audio_test', 0)
            ->orderBy('user_word.word_id', 'DESC')
            ->select(
                'word.name',
                'user_word.*',
                'word.id',
                'word.translate',
                'word.description',
                'word.type',
                'word.prsi',
                'word.prsh',
                'word.pas',
                'word.pas2',
                'word.pasp',
                'word.pasp2',
                'word.ing',
                'word.plural',
            )
            ->limit(6)
            ->get();

        foreach ($words as &$word) {
            $spells = str_split($word->name);
            $spellNames = [];
            foreach ($spells as $spell) {
                $spellNames[] = [
                    'character' => $spell,
                    'show' => false,
                ];
            }
            $word->spell = $spellNames;
        }
        return response()->json(['status' => 'success', 'data' => $words], 200);
    }

    public function changeStatus(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $word = UserWord::where('user_id', $authUser)->where('word_id', $request->get('id'))->first();
        if ($request->get('check') == 'wt') {
            $word->update(['wt' => 1]);
        }
        if ($request->get('check') == 'tw') {
            $word->update(['tw' => 1]);
        }
        if ($request->get('check') == 'audio_test') {
            $word->update(['audio_test' => 1]);
        }
        if ($word->wt == 1 && $word->tw == 1 && $word->audio_test == 1) {
            $date = Carbon::now();
            $word->update(['start_repeat' => $date]);
        }
        return response()->json(['status' => 'success', 'data' => 'nice'], 200);
    }

    public function changeStatusId(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $word = Word::where('id', $request->get('id'))->first();
        $pieces = explode(' ', $word->name);
        UserWord::where('user_id', $authUser)
            ->where('word_id', $request->get('id'))
            ->update(['wt' => 0, 'tw' => 0, 'audio_test' => count($pieces) > 1 ? 1 : 0]);
        return response()->json(['status' => 'success', 'data' => 'nice'], 200);
    }

    public function changeRepeat(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $word = UserWord::where('user_id', $authUser)->where('word_id', $request->get('id'))->first();
        $pieces = explode(' ', $word->name);
        if ($request->get('check') == 'true') {
            $word->update([
                'count_repeat' => $word->count_repeat + 1,
                'start_repeat' => Carbon::now()->addDays(($word->count_repeat + 1) * 2),
            ]);
        } else {
            $word->update([
                'audio_test' => count($pieces) > 1 ? 1 : 0,
                'tw' => 0,
                'wt' => 0,
            ]);
        }
        return response()->json(['status' => 'success', 'data' => 'nice'], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $from = Carbon::now();
        $counts = UserWord::where('user_id', $authUser)
            ->selectRaw(
                "SUM(CASE WHEN tw = 1 AND wt = 1 AND audio_test = 1 AND start_repeat < ? THEN 1 ELSE 0 END) as repeat_count,\n" .
                    "SUM(CASE WHEN audio_test = 0 THEN 1 ELSE 0 END) as audio_count,\n" .
                    "SUM(CASE WHEN wt = 0 THEN 1 ELSE 0 END) as wt_count,\n" .
                    'SUM(CASE WHEN tw = 0 THEN 1 ELSE 0 END) as tw_count',
                [$from],
            )
            ->first();

        return response()->json(
            [
                'status' => 'success',
                'repeat' => (int) ($counts->repeat_count ?? 0),
                'audio' => (int) ($counts->audio_count ?? 0),
                'wt' => (int) ($counts->wt_count ?? 0),
                'tw' => (int) ($counts->tw_count ?? 0),
            ],
            200,
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getRepeat(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $from = Carbon::now();
        $words = UserWord::where('user_id', $authUser)
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('tw', 1)
            ->where('wt', 1)
            ->where('audio_test', 1)
            ->where('start_repeat', '<', $from)
            ->select(
                'word.name',
                'user_word.*',
                'word.id',
                'word.translate',
                'word.description',
                'word.type',
                'word.prsi',
                'word.prsh',
                'word.pas',
                'word.pas2',
                'word.pasp',
                'word.pasp2',
                'word.ing',
                'word.plural',
            )
            ->inRandomOrder()
            ->limit(6)
            ->get();
        foreach ($words as &$word) {
            $toTranslate = Word::select('translate')->limit(1)->inRandomOrder()->pluck('translate')->toArray();
            if (!in_array($word->translate, $toTranslate)) {
                $toTranslate[] = $word->translate;
            }
            shuffle($toTranslate);
            $word->checkTranslate = $toTranslate;
        }
        return response()->json(['status' => 'success', 'data' => $words], 200);
    }

    /**
     * Send a translation request to Microsoft Translator API.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function translateWord(Request $request): JsonResponse
    {
        $request->validate([
            'word' => 'required|string|min:1',
        ]);

        $subscriptionKey = config('app.translator_key');
        if (empty($subscriptionKey)) {
            return response()->json(['status' => 'error', 'message' => 'Translator key is not configured'], 500);
        }

        $baseUrl = 'https://api.cognitive.microsofttranslator.com/translate';

        $headers = [
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Ocp-Apim-Subscription-Region' => 'eastus',
            'Content-type' => 'application/json',
            'X-ClientTraceId' => (string) Str::uuid(),
        ];

        $query = [
            'api-version' => '3.0',
            'from' => 'en',
            'to' => 'uk',
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withOptions(['query' => $query])
                ->post($baseUrl, [
                    [
                        'Text' => $request->get('word'),
                    ],
                ]);
            if (!$response->ok()) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Translation API error',
                        'details' => $response->json(),
                    ],
                    $response->status(),
                );
            }

            $data = $response->json();
            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed to contact translation service',
                    'details' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
