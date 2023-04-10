<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const PER_PAGE_DEFAULT = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'patronymic',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function words()
    {
        return $this->belongsToMany('App\Models\Word');
    }


    /** Returns account transactions
     * @param array $with
     * @param int $perPage
     * @param bool $sortType
     * @param string $sortBy
     * @return LengthAwarePaginator
     */
    public static function getUsers($with = [], $perPage = User::PER_PAGE_DEFAULT,
                                    $sortType = false, $sortBy = 'created_at'
    ): LengthAwarePaginator
    {
        $query = new User();

        $query = $query->with($with);

        $sortType = $sortType == 'true' ? 'desc' : 'asc';
        return $query->orderBy($sortBy,$sortType)->paginate($perPage);
    }
}
