<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portofolio extends Model
{
    protected $guarded = [];

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }


    public function realBalance()
    {
        return $this->transaction()->sum('amount');
    }

    public function currentBalance()
    {
        return $this->realBalance() + (($this->realBalance() * $this->interest_rate) / 100);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
