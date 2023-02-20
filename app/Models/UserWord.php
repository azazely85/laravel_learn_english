<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

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
        'start_repeat'
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
    public static function getWords($with = [], $perPage = Word::PER_PAGE_DEFAULT,
                                    $sortType = false, $sortBy = 'created_at'): LengthAwarePaginator
    {
        $query = new User();

        $query = $query->with($with);

        $sortType = $sortType == 'true' ? 'desc' : 'asc';
        return $query->orderBy($sortBy,$sortType)->paginate($perPage);
    }
}
