<?php

namespace App\Console\Commands;

use App\Models\UserWord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Word;
use Carbon\Carbon;

class UpdateUserWord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userword:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update UserWord model records with specified criteria';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting UserWord update process...');

        // Build query
        $userWords = UserWord::all();
        foreach ($userWords as $userWord) {
            $word = Word::find($userWord->word_id);
            $pieces = explode(" ", $word->name);
            if (count($pieces) > 1) {
                $userWord->audio_test = 1;
            }
            $userWord->save();
        }
        $words = Word::all();
        foreach ($words as $word) {
            $userWord = UserWord::where('user_id', 2)
                ->where('word_id', $word->id)->first();
            $date = Carbon::now();
            if (!$userWord) {
                UserWord::create([
                    'user_id' => 2,
                    'word_id' => $word->id,
                    'wt' => 0,
                    'tw' => 0,
                    'audio_test' => count($pieces) > 1 ? 1 : 0,
                    'start_repeat' => $date
                ]);
            }
        }
        $this->info('UserWord update process completed successfully.');
    }
}
