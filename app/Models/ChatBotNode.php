<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatBotNode extends Model
{
    protected $fillable = [
        'node_key',
        'message',
        'images',
        'dynamic_content',
        'status',
    ];
    public function options()
    {
        return $this->hasMany(ChatBotNodeOption::class)->where('status', 'active');
    }

    public function allOptions()
    {
        return $this->hasMany(ChatBotNodeOption::class);
    }
}
