<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Word
 *
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string|null $veb_forms
 * @property string $description
 * @property string|null $translate
 * @property string|null $prsi
 * @property string|null $prsh
 * @property string|null $pas
 * @property string|null $pas2
 * @property string|null $pasp
 * @property string|null $pasp2
 * @property string|null $ing
 * @property string|null $comparative
 * @property string|null $superlative
 * @property string|null $plural
 * @method static Builder|Word newModelQuery()
 * @method static Builder|Word newQuery()
 * @method static Builder|Word query()
 * @method static Builder|Word whereComparative($value)
 * @method static Builder|Word whereDescription($value)
 * @method static Builder|Word whereId($value)
 * @method static Builder|Word whereIng($value)
 * @method static Builder|Word whereName($value)
 * @method static Builder|Word wherePas($value)
 * @method static Builder|Word wherePas2($value)
 * @method static Builder|Word wherePasp($value)
 * @method static Builder|Word wherePasp2($value)
 * @method static Builder|Word wherePlural($value)
 * @method static Builder|Word wherePrsh($value)
 * @method static Builder|Word wherePrsi($value)
 * @method static Builder|Word whereSuperlative($value)
 * @method static Builder|Word whereTranslate($value)
 * @method static Builder|Word whereType($value)
 * @method static Builder|Word whereVebForms($value)
 * @mixin \Eloquent
 */
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
    public static function getWords(array $with = [], int $perPage = Word::PER_PAGE_DEFAULT,
                                    bool  $sortType = false, string $sortBy = 'created_at'): LengthAwarePaginator
    {
        $query = new User();

        $query = $query->with($with);

        $sortType = $sortType == 'true' ? 'desc' : 'asc';
        return $query->orderBy($sortBy,$sortType)->paginate($perPage);
    }
}
