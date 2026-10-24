<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];
    protected static function booted(): void
    {
        static::creating(function (Client $client) { $client->public_token ??= Str::random(40); });
    }
    public function contents() { return $this->hasMany(Content::class); }
    public function users() { return $this->hasMany(User::class); }
    public function teamMembers() { return $this->belongsToMany(User::class, 'client_team')->withPivot('assigned_by')->withTimestamps(); }
}
