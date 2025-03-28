<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['project_id', 'user_id', 'role', 'content'];

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
