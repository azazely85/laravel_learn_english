<?php

namespace App\Models;

use Eloquent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserWord
 *
 * @property int $user_id
 * @property int $word_id
 * @property int $wt
 * @property int $tw
 * @property int $audio_test
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $start_repeat
 * @property int $id
 * @property int $count_repeat
 * @method static Builder|UserWord newModelQuery()
 * @method static Builder|UserWord newQuery()
 * @method static Builder|UserWord query()
 * @method static Builder|UserWord whereAudioTest($value)
 * @method static Builder|UserWord whereCountRepeat($value)
 * @method static Builder|UserWord whereCreatedAt($value)
 * @method static Builder|UserWord whereId($value)
 * @method static Builder|UserWord whereStartRepeat($value)
 * @method static Builder|UserWord whereTw($value)
 * @method static Builder|UserWord whereUpdatedAt($value)
 * @method static Builder|UserWord whereUserId($value)
 * @method static Builder|UserWord whereWordId($value)
 * @method static Builder|UserWord whereWt($value)
 * @mixin Eloquent
 */
class UserWord extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'wt',
        'tw',
        'audio_test',
        'date',
        'start_repeat',
        'count_repeat',
        'user_id'
    ];
    protected $table = 'user_word';
    public $timestamps = false;

    /** Returns account transactions
     * @param array $with
     * @param int $perPage
     * @param bool $sortType
     * @param string $sortBy
     * @return LengthAwarePaginator
     */
    public static function getWords(array $with = [], int $perPage = Word::PER_PAGE_DEFAULT,
                                    bool  $sortType = false, string $sortBy = 'created_at'
    ): LengthAwarePaginator
    {
        $query = new User();

        $query = $query->with($with);

        $sortType = $sortType == 'true' ? 'desc' : 'asc';
        return $query->orderBy($sortBy, $sortType)->paginate($perPage);
    }
}
