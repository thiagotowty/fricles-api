<?php

namespace App\Models;

class Conversas extends Base
{
    protected $table = "conversa";
    protected $hidden = ['created_at', 'updated_at'];

    public static function getBySender($sender_id)
    {
        return Conversas::where('sender', $sender_id)
            ->first();
    }

}
