<?php

namespace Modules\User\Entities;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Modules\User\Entities\PasswordResets;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'username', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'deleted_at',
    ];

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    // protected $visible = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'id_str', 'full_name', 'is_admin', 'is_editor',  'is_deleted'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'data' => collect($this->toArray())->only([
                'access_level', 'is_admin'
            ])
        ];
    }

    /**
     * Scope a query to include all relationships.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAll($query)
    {
        return $query->with($this->allModelRelations);
    }

    /**
     * Scope a query to only include admin users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsAdmin($query)
    {
        return $query->where('access_level', 1);
    }

    /**
     * Scope a query to only include editors.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsEditor($query)
    {
        return $query->where('access_level', 2);
    }

    // public function scopeIsGuest($query)
    // {
    //     return $query->where('is_guest', 1);
    // }

    /**
     * Scope a query to only include users without an admin role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotAdmin($query)
    {
        return $query->where('access_level', '!=', 1);
    }

    /**
     * Scope a query to only include normal users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNormalUser($query)
    {
        return $query->where('access_level', 0);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope a query to only include deactivated users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotActive($query)
    {
        return $query->where('is_active', 0);
    }

    /**
     * Returns the user password resets
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function passwordResets()
    {
        return $this->hasMany(PasswordResets::class, 'email', 'email');
    }

    /**
     * Returns the user token object
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    // public function tokens()
    // {
    //     return $this->hasMany(Token::class, 'user_id');
    // }

    public function getIdStrAttribute()
    {
        return (string) $this->attributes['id'];
    }

    public function getFullNameAttribute()
    {
        return $this->attributes['first_name'].' '.$this->attributes['last_name'];
    }

    public function getAccessLevelAttribute()
    {
        switch ($this->attributes['access_level']) {
            case 1:
                return [
                    'code' => $this->attributes['access_level'],
                    'name' => 'Admin',
                ];
                break;
            case 2:
                return [
                    'code' => $this->attributes['access_level'],
                    'name' => 'Editor',
                ];
                break;
            
            default:
                return [
                    'code' => $this->attributes['access_level'],
                    'name' => 'Member',
                ];
                break;
        }
    }

    public function getIsAdminAttribute()
    {
        return ((int) $this->attributes['access_level']) === 1;
    }

    public function getIsEditorAttribute()
    {
        return ((int) $this->attributes['access_level']) === 2;
    }

    public function getIsDeletedAttribute()
    {
        return ! is_null($this->attributes['deleted_at']);
    }

    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->toIso8601String();
    }

    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->toIso8601String();
    }
}
