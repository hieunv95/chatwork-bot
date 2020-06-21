<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    const INIT_TYPE = 1,
        REGISTER_TYPE = 2,
        PREVIEW_TYPE = 3,
        CONFIRMED_TYPE = 4;

    const CONFIRMED_STATUS = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'room_id',
        'account_id',
        'account_name',
        'parent_order_id',
        'type',
        'ordered_quantity',
        'status',
    ];

    protected $casts = [
        'room_id' => 'integer',
        'account_id' => 'integer',
        'parent_order_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Order::class, 'parent_order_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function registeredOrders()
    {
        return $this->children()->where('type', self::REGISTER_TYPE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function previewOrders()
    {
        return $this->children()->where('type', self::PREVIEW_TYPE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id', 'id');
    }

    /**
     * Get the order quantity attribute.
     *
     * @param $value
     *
     * @return string
     */
    public function getOrderedQuantityAttribute($value)
    {
        if ($value > 0) {
            return '+' . $value;
        }

        return $value;
    }
}
