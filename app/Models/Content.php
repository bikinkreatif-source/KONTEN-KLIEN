<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['publish_date' => 'date', 'due_date' => 'date', 'checklist' => 'array'];
    public function client() { return $this->belongsTo(Client::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function revisions() { return $this->hasMany(ContentRevision::class)->latest(); }
}
