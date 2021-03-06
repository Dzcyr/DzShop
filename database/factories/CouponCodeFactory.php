<?php

namespace Database\Factories;

use App\Models\CouponCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponCodeFactory extends Factory
{
    protected $model = CouponCode::class;

    public function definition()
    {
        // 随机获取一个类型
        $type = $this->faker->randomElement(array_keys(CouponCode::$typeMap));
        $value = $type === CouponCode::TYPE_FIXED ? random_int(1, 200) : random_int(1, 50);
        // 如果是固定金额，则最低订单金额必须要比优惠金额高0.01
        if($type === CouponCode::TYPE_FIXED) {
            $minAmount = $value + 0.01;
        } else {
            // 如果是百分比折扣,有50%的概率不需要最低订单金额
            if(random_int(0, 100) < 50) {
                $minAmount = 0;
            } else {
                $minAmount = random_int(100, 1000);
            }
        }
        return [
            'name' => join('', $this->faker->words),
            'code' => CouponCode::findAvailableCode(),
            'type' => $type,
            'value' => $value,
            'total' => 1000,
            'used' => 0,
            'min_amount' => $minAmount,
            'not_before' => null,
            'not_after' => null,
            'enabled' => true,
        ];
    }
}
