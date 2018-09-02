<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
    public function salvar()
    {
        $this->save();
        return $this;
    }

    public function excluir()
    {
        $this->delete();
        return $this;
    }

    public static function salvarMuitos($objeto)
    {
        foreach ($objeto as $item)
            $item->save();

        return $objeto;
    }

    public static function excluirMuitos($objeto)
    {
        foreach ($objeto as $item)
            $item->delete();

        return $objeto;
    }
}
