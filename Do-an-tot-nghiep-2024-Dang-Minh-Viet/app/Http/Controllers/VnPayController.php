<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VnPayController extends Controller
{
    public function payment(Request $request)
{
    // Kiểm tra tổng số tiền
    if (!$request->has('total') || $request->total < 5000 || $request->total >= 1000000000) {
        return redirect()->route('home')->with('error', 'Số tiền không hợp lệ. Số tiền phải từ 5,000 đến dưới 1 tỷ đồng.');
    }

    // VNPay API Configuration
    $vnp_Url = "http://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
    $vnp_Returnurl = route('vnpay.return');
    $vnp_TmnCode = "2YPTE6EH";
    $vnp_HashSecret = "0DQG7MXM7ZS5LHKAP73WESVWET1JRLOY";

    // Data from Request
    $vnp_TxnRef = time();
    $vnp_OrderInfo = "Thanh toán đơn hàng tại VietShop";
    $vnp_OrderType = "billpayment";
    $vnp_Amount = intval($request->total) * 100; // Tính số tiền theo VNPay
    $vnp_Locale = "vn";
    $vnp_IpAddr = $request->ip();

    $inputData = [
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
    ];

    // Sort data and generate hash
    ksort($inputData);
    $query = http_build_query($inputData);
    $hashData = urldecode($query);

    if ($vnp_HashSecret) {
        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $query .= '&vnp_SecureHash=' . $vnpSecureHash;
    }

    $vnp_Url .= "?" . $query;

    // Redirect to VNPay
    return redirect($vnp_Url);
}


    public function vnpayReturn(Request $request)
    {
        $vnp_SecureHash = $request->vnp_SecureHash;
        $inputData = $request->except('vnp_SecureHash', 'vnp_SecureHashType');
        ksort($inputData);
        $hashData = urldecode(http_build_query($inputData));
        $secureHash = hash_hmac('sha512', $hashData, "0DQG7MXM7ZS5LHKAP73WESVWET1JRLOY");

        if ($vnp_SecureHash === $secureHash) {
            if ($request->vnp_ResponseCode == '00') {
                return redirect()->route('home')->with('success', 'Thanh toán thành công!');
            } else {
                return redirect()->route('home')->with('error', 'Thanh toán thất bại!');
            }
        } else {
            return redirect()->route('home')->with('error', 'Chữ ký không hợp lệ!');
        }
    }
}
