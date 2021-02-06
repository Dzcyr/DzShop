<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Order;
use App\Models\CouponCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Generator as Faker;
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // 随机取一个用户
        $user = User::query()->inRandomOrder()->first();
        // 随机取一个该用户的地址
        $address = $user->addresses()->inRandomOrder()->first();
        // 10% 概率把订单标记为退款
        $refund = random_int(0, 10) < 1;
        // 随机生成发货状态
        $ship = $this->faker->randomElement(array_keys(Order::$shipStatusMap));
        // 优惠卷
        $coupon = null;
        // 百分之30的概率 使用了优惠卷
        if(random_int(0, 10) < 3) {
            // 为了避免出现逻辑错误，只选择没有最低金额限制的优惠券
            $coupon = CouponCode::query()->where('min_amount', 0)->inRandomOrder()->first();
            // 增加优惠卷的使用量
            $coupon->changeUsed();
        }
        return [
            'address' => [
                'address' => $address->full_address,
                'zip' => $address->zip,
                'contact_name' => $address->contact_name,
                'contact_phone' => $address->contact_phone,
            ],
            'total_amount' => 0,
            'remark' => $this->faker->sentence,
            'paid_at' => $this->faker->dateTimeBetween('-30 days'),
            'payment_method' => $this->faker->randomElement(['alipay', 'wechat']),
            'payment_no' => $this->faker->uuid,
            'refund_status' => $refund ? Order::REFUND_STATUS_SUCCESS : Order::REFUND_STATUS_PENDING,
            'refund_no' => $refund ? Order::getAvailableRefundNo() : null,
            'closed' => false,
            'reviewed' => random_int(0, 10) > 2,
            'ship_status' => $ship,
            'ship_data' => $ship === Order::SHIP_STATUS_PENDING ? null : [
                'express_company' => $this->faker->company,
                'express_no' => $this->faker->uuid,
            ],
            'extra' => $refund ? ['refund_reason' => $this->faker->sentence] : [],
            'user_id' => $user->id,
            'coupon_code_id' => $coupon ? $coupon->id : null,
        ];
    }
}
