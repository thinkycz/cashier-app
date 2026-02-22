<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_id',
        'vat_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'street',
        'city',
        'zip',
        'country_code',
    ];

    protected $appends = [
        'full_name',
        'display_name',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name !== '' ? $this->full_name : $this->company_name;
    }
}
