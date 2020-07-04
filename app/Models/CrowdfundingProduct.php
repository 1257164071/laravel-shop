<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrowdfundingProduct extends Model
{
    //
    const STATUS_FUNDING = 'funding';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    public static $statusMap = [
        self::STATUS_FUNDING => '众筹中',
        self::STATUS_SUCCESS => '众筹成功',
        self::STATUS_FAIL => '众筹失败',
    ];

    protected $fillable = ['total_amount', 'target_amount', 'user_count', 'status', 'end_at'];

    protected $dates = ['end_at'];

    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getPercentAttribute()
    {
        $value = $this->attributes['total_amount'] / $this->['target_amount'];

        return floatval(number_format($value * 100,'.', ''));
    }


}
