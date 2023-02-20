<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{

    const PER_PAGE_DEFAULT = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'veb_forms',
        'type',
        'description',
        'translate',
        'comparative',
        'superlative',
        'prsi',
        'prsh',
        'pas',
        'pas2',
        'pasp',
        'pasp2',
        'ing',
        'plural'
    ];
    protected $table = 'word';
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
