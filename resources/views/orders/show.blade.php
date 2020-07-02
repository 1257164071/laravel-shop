@extends('layouts.app')
@section('title', '查看订单')

@section('content')
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>订单详情</h4>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>商品信息</th>
                            <th class="text-center">单价</th>
                            <th class="text-center">数量</th>
                            <th class="text-right item-amount">小计</th>
                        </tr>
                        </thead>
                        @foreach($order->items as $index => $item)
                            <tr>
                                <td class="product-info">
                                    <div class="preview">
                                        <a target="_blank" href="{{ route('products.show', [$item->product_id]) }}">
                                            <img src="{{ $item->product->image_url }}">
                                        </a>
                                    </div>
                                    <div>
            <span class="product-title">
               <a target="_blank"
                  href="{{ route('products.show', [$item->product_id]) }}">{{ $item->product->title }}</a>
             </span>
                                        <span class="sku-title">{{ $item->productSku->title }}</span>
                                    </div>
                                </td>
                                <td class="sku-price text-center vertical-middle">￥{{ $item->price }}</td>
                                <td class="sku-amount text-center vertical-middle">{{ $item->amount }}</td>
                                <td class="item-amount text-right vertical-middle">
                                    ￥{{ number_format($item->price * $item->amount, 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4"></td>
                        </tr>
                    </table>
                    <div class="order-bottom">
                        <div class="order-info">
                            <div class="line">
                                <div class="line-label">收货地址：</div>
                                <div class="line-value">{{ join(' ', $order->address) }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单备注：</div>
                                <div class="line-value">{{ $order->remark ?: '-' }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单编号：</div>
                                <div class="line-value">{{ $order->no }}</div>
                            </div>
                            <!-- 输出物流状态 -->
                            <div class="line">
                                <div class="line-label">物流状态：</div>
                                <div class="line-value">{{ \App\Models\Order::$shipStatusMap[$order->ship_status] }}</div>
                            </div>
                            <!-- 如果有物流信息则展示 -->
                            @if($order->ship_data)
                                <div class="line">
                                    <div class="line-label">物流信息：</div>
                                    <div class="line-value">{{ $order->ship_data['express_company'] }} {{ $order->ship_data['express_no'] }}</div>
                                </div>
                            @endif
                        <!-- 订单已支付，且退款状态不是未退款时展示退款信息 -->
                            @if($order->paid_at && $order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING)
                                <div class="line">
                                    <div class="line-label">退款状态：</div>
                                    <div class="line-value">{{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}</div>
                                </div>
                                <div class="line">
                                    <div class="line-label">退款理由：</div>
                                    <div class="line-value">{{ $order->extra['refund_reason'] }}</div>
                                </div>
                            @endif
                        </div>
                        <div class="order-summary text-right">
                            <!-- 展示优惠信息开始 -->
                            @if($order->couponCode)
                                <div class="text-primary">
                                    <span>优惠信息：</span>
                                    <div class="value">{{ $order->couponCode->description }}</div>
                                </div>
                            @endif
                        <!-- 展示优惠信息结束 -->
                            <div class="total-amount">
                                <span>订单总价：</span>
                                <div class="value">￥{{ $order->total_amount }}</div>
                            </div>
                            <div>
                                <span>订单状态：</span>
                                <div class="value">
                                    @if($order->paid_at)
                                        @if($order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                                            已支付
                                        @else
                                            {{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}
                                        @endif
                                    @elseif($order->closed)
                                        已关闭
                                    @else
                                        未支付
                                    @endif
                                </div>
                            </div>
                            @if(isset($order->extra['refund_disagree_reason']))
                                <div>
                                    <span>拒绝退款理由：</span>
                                    <div class="value">{{ $order->extra['refund_disagree_reason'] }}</div>
                                </div>
                            @endif
                        <!-- 支付按钮开始 -->
                            @if(!$order->paid_at && !$order->closed)
                                <div class="payment-buttons">
                                    <a class="btn btn-primary btn-sm"
                                       href="{{ route('payment.alipay', ['order' => $order->id]) }}">支付宝支付</a>
                                    <button class="btn btn-sm btn-success" id='btn-wechat'>微信支付</button>
                                </div>
                            @endif
                        <!-- 支付按钮结束 -->
                            <!-- 如果订单的发货状态为已发货则展示确认收货按钮 -->
                            @if($order->ship_status === \App\Models\Order::SHIP_STATUS_DELIVERED)
                                <div class="receive-button">
                                    <button type="button" id="btn-receive" class="btn btn-sm btn-success">确认收货</button>
                                </div>
                            @endif
                        <!-- 订单已支付，且退款状态是未退款时展示申请退款按钮 -->
                            @if($order->paid_at && $order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                                <div class="refund-button">
                                    <button class="btn btn-sm btn-danger" id="btn-apply-refund">申请退款</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scriptsAfterJs')
    <script>
      $(document).ready(function () {
        // 『不同意』按钮的点击事件
        $('#btn-refund-disagree').click(function () {
          swal({
            title: '输入拒绝退款理由',
            input: 'text',
            showCancelButton: true,
            confirmButtonText: "确认",
            cancelButtonText: "取消",
            showLoaderOnConfirm: true,
            preConfirm: function (inputValue) {
              if (!inputValue) {
                swal('理由不能为空', '', 'error')
                return false;
              }
              // Laravel-Admin 没有 axios，使用 jQuery 的 ajax 方法来请求
              return $.ajax({
                url: '{{ route('admin.orders.handle_refund', [$order->id]) }}',
                type: 'POST',
                data: JSON.stringify({   // 将请求变成 JSON 字符串
                  agree: false,  // 拒绝申请
                  reason: inputValue,
                  // 带上 CSRF Token
                  // Laravel-Admin 页面里可以通过 LA.token 获得 CSRF Token
                  _token: LA.token,
                }),
                contentType: 'application/json',  // 请求的数据格式为 JSON
              });
            },
            allowOutsideClick: () => !swal.isLoading()
          }).then(function (ret) {
            // 如果用户点击了『取消』按钮，则不做任何操作
            if (ret.dismiss === 'cancel') {
              return;
            }
            swal({
              title: '操作成功',
              type: 'success'
            }).then(function () {
              // 用户点击 swal 上的按钮时刷新页面
              location.reload();
            });
          });
        });

        // 『同意』按钮的点击事件
        $('#btn-refund-agree').click(function () {
          swal({
            title: '确认要将款项退还给用户？',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: "确认",
            cancelButtonText: "取消",
            showLoaderOnConfirm: true,
            preConfirm: function () {
              return $.ajax({
                url: '{{ route('admin.orders.handle_refund', [$order->id]) }}',
                type: 'POST',
                data: JSON.stringify({
                  agree: true, // 代表同意退款
                  _token: LA.token,
                }),
                contentType: 'application/json',
              });
            }
          }).then(function (ret) {
            // 如果用户点击了『取消』按钮，则不做任何操作
            if (ret.dismiss === 'cancel') {
              return;
            }
            swal({
              title: '操作成功',
              type: 'success'
            }).then(function () {
              // 用户点击 swal 上的按钮时刷新页面
              location.reload();
            });
          });
        });

      });
    </script>
@endsection
