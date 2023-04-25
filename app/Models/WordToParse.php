<?php

namespace App\Models;

use Eloquent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\WordToParse
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @method static Builder|WordToParse newModelQuery()
 * @method static Builder|WordToParse newQuery()
 * @method static Builder|WordToParse query()
 * @method static Builder|WordToParse whereId($value)
 * @method static Builder|WordToParse whereName($value)
 * @method static Builder|WordToParse whereUrl($value)
 * @mixin Eloquent
 */
class WordToParse extends Model
{

    const PER_PAGE_DEFAULT = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'url'
    ];

    protected $table = 'word_to_parse';
    public $timestamps = false;

    /** Returns account transactions
     * @param array $with
     * @param int $perPage
     * @param bool $sortType
     * @param string $sortBy
     * @return LengthAwarePaginator
     */
    public static function getWords(array $with = [], int $perPage = WordToParse::PER_PAGE_DEFAULT,
                                    bool  $sortType = false, string $sortBy = 'created_at'): LengthAwarePaginator
    {
        $query = new User();

        $query = $query->with($with);

        $sortType = $sortType == 'true' ? 'desc' : 'asc';
        return $query->orderBy($sortBy,$sortType)->paginate($perPage);
    }
}
