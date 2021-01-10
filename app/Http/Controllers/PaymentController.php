<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Events\OrderPaid;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use App\Exceptions\InvalidRequestException;

class PaymentController extends Controller
{
    // 支付宝
    public function payByAlipay(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或者已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // 调用支付宝的网页支付
        return app('alipay')->web([
            // 商户订单流水号
            'out_trade_no' => $order->no,
            // 订单金额，单位元，支持小数点后两位
            'total_amount' => $order->total_amount,
            // 订单标题
            'subject' => '支付订单：'.$order->no,
        ]);
    }

    // 微信
    public function payByWechat(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或者已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // scan 方法为拉起微信扫码支付
        $wechatOrder = app('wechat_pay')->scan([
            // 商户订单流水号
            'out_trade_no' => $order->no,
            // 订单金额，单位分
            'total_fee' => $order->total_amount * 100,
            // 订单标题
            'body' => '支付订单：'.$order->no,
        ]);
        // 把要转换的字符串作为 QrCode 的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);
        // 将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    // 前端回调页面
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    // 支付宝 服务器端回调
    public function alipayNotify()
    {
        // 校验输入参数
        $data  = app('alipay')->verify();
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        if(!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }
        // 判断订单是否存在
        $order = Order::where('no', $data->out_trade_no)->first();
        if (!$order) {
            return 'fail';
        }
        // 如果这笔订单的状态已经是已支付
        if ($order->paid_at) {
            // 返回数据给支付宝
            return app('alipay')->success();
        }

        $order->update([
            // 支付时间
            'paid_at' => Carbon::now(),
            // 支付方式
            'payment_method' => 'alipay',
            // 支付宝订单号
            'payment_no' => $data->trade_no,
        ]);

        $this->afterPaid($order);

        return app('alipay')->success();
    }

    // 微信 服务器端回调
    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data  = app('wechat_pay')->verify();
        // 找到对应的订单
        $order = Order::where('no', $data->out_trade_no)->first();
        // 订单不存在则告知微信支付
        if (!$order) {
            return 'fail';
        }
        // 订单已支付
        if ($order->paid_at) {
            // 告知微信支付此订单已处理
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }

    public function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }
}
