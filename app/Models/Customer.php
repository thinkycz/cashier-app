<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
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
        if ($this->full_name !== '') {
            return $this->full_name;
        }

        if (is_string($this->company_name) && $this->company_name !== '') {
            return $this->company_name;
        }

        return 'Unknown customer';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        $query = $this->newQuery()->where($field, $value);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        }

        return $query->firstOrFail();
    }
}
