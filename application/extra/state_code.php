<?php
// +----------------------------------------------------------------------
// | 配置业务状态码
// +----------------------------------------------------------------------

return [
    # 10000 通用错误
    '10000' => 'Undefined Error Message', // 未定义错误
    '10001' => 'Validation Error', // 验证错误
    '10002' => 'Repeat submission. Please try again or refresh the page',  //重复提交，请重试或刷新页面
    '10003' => 'Incorrect Parameter',  // 参数不正确
    '10004' => 'No Record Found', // 没有找到此记录
    '10005' => 'Save Failed',    // 保存时报
    '10006' => 'Change Failed ',  // 修改失败
    '10007' => 'Failed to Delete',   // 删除失败
    '10010' => 'You don\'t have permission to operate', // 你没有操作权限
    '10012' => 'Request Error',
    '10014' => 'Illegal request method',
    '10050' => 'Server internal error',
    '10060' => 'e-mail sending failed',
    '10070' => 'The data is incomplete.',
    '10404' => '404',
    # 11000 用户
    '11000' => 'User does not exist',   //用户不存在
    '11001' => 'This account has been disabled', //此账号已停用
    '11002' => 'Incorrect password, please try again', // 密码错误，请重试
    '11003' => 'The new password is the same as the old password', // 新密码和旧密码一致
    '11005' => 'User registration failed!', // 新密码和旧密码一致
    '11401' => 'Not Logged In', // 未登录

    #12000 订单
    '12000' => 'Order does not exist',
    '12001' => 'Order paid',
    '12002' => 'Incomplete data submitted, unable to pay',  // 提交数据不完整，无法进行支付
    '12003' => 'Order status update failed',
    '12004' => 'Order data update failed',
    '12005' => 'PayPal payment order creation failed',
    '12006' => 'Please apply for WSE Degree Assessed Education Storage service before applying for this project.',

    '13000' => 'Failed to obtain verification code',
    #20000 成功操作
    '20000' => 'Successful', // 通用成功操作
];