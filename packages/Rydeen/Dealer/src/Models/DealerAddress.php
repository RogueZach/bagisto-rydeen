<?php

namespace Rydeen\Dealer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\Customer;

class DealerAddress extends Model
{
    protected $table = 'rydeen_dealer_addresses';

    protected $fillable = [
        'customer_id',
        'label',
        'first_name',
        'last_name',
        'company_name',
        'address1',
        'address2',
        'city',
        'state',
        'postcode',
        'country',
        'phone',
        'is_approved',
        'is_default',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_default'  => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForDealer($query, int $dealerId)
    {
        return $query->where('customer_id', $dealerId);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address1,
            $this->address2,
            $this->city,
            $this->state . ' ' . $this->postcode,
        ]);

        return implode(', ', $parts);
    }
}
