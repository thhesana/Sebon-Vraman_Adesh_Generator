<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the legacy `Users` table onto Laravel's authentication system.
 * The password lives in `password_hash`; the table has no remember_token column.
 */
class User extends Model implements Authenticatable
{
    protected $table = 'Users';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['password_hash'];

    public function getAuthIdentifierName(): string
    {
        return 'user_id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->user_id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // No remember_token column in the legacy Users table.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
