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

class LearningController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranslateWord(Request $request)
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('user_word.tw', 0)
            ->select('word.name', 'user_word.*', 'word.id', 'word.translate')
            ->limit(6)
            ->get();
        foreach ($words as &$word) {
            $toTranslate = Word::select('name')->limit(5)->inRandomOrder()->pluck('name')->toArray();
            if (!in_array($word->name, $toTranslate)) {
                $toTranslate[] = $word->name;
            }
            shuffle($toTranslate);
            $word->checkName = $toTranslate;
        }

        return response()->json(['status' => 'success', 'data' => $words], 200);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getWordTranslate(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('user_word.wt', 0)
            ->select('word.name', 'user_word.*', 'word.id', 'word.translate', 'word.description', 'word.type')
            ->limit(6)
            ->get();
        foreach ($words as &$word) {
            $toTranslate = Word::select('translate')->limit(5)->inRandomOrder()->pluck('translate')->toArray();
            if (!in_array($word->translate, $toTranslate)) {
                $toTranslate[] = $word->translate;
            }
            shuffle($toTranslate);
            $word->checkTranslate = $toTranslate;
        }

        return response()->json(['status' => 'success', 'data' => $words], 200);

    }

    public function getWordAudio(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $words = User::where('users.id', $authUser)
            ->leftJoin('user_word', 'user_word.user_id', '=', 'users.id')
            ->leftJoin('word', 'user_word.word_id', '=', 'word.id')
            ->where('user_word.audio_test', 0)
            ->select('word.name', 'user_word.*', 'word.id', 'word.translate', 'word.description', 'word.type')
            ->limit(6)
            ->get();

        foreach ($words as &$word) {
            $spells = str_split($word->name);
            $spell_names = [];
            foreach ($spells as $spell) {
                $spell_names[] = [
                    'character' => $spell,
                    'show' => false
                ];
            }
            $word->spell = $spell_names;
        }
        return response()->json(['status' => 'success', 'data' => $words], 200);

    }

    public function changeStatus(Request $request): JsonResponse
    {
        $authUser = Auth::id();
        $word = UserWord::where('user_id', $authUser)
            ->where('word_id', $request->get('id'))->first();
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function countRepeat(Request $request)
    {
        $authUser = Auth::id();
        $from1 = Carbon::now()->subDay();
        $from2 = Carbon::now()->subDays(3);
        $from3 = Carbon::now()->subDays(5);
        $wordsCount = UserWord::where('user_id', $authUser)
            ->where('tw', 1)
            ->where('wt', 1)
            ->where('audio_test', 1)
            ->where(function ($query) use ($from1, $from2, $from3) {
                $query->orWhere('start_repeat', '<', $from1)
                    ->orWhere('start_repeat', '<', $from2)
                    ->orWhere('start_repeat', '<', $from3);
            })
            ->count();

        return response()->json(['status' => 'success', 'data' => $wordsCount], 200);

    }
}
