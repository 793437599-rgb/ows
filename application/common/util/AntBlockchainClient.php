<?php
/**
 * 上传数据到蚂蚁金服区块链接口
 * 
 * 需要提前准备：
 * 1. 蚂蚁金服区块链API访问权限
 * 2. App ID和App Key
 * 3. 区块链合约地址
 */

class AntBlockchainClient {
    // 蚂蚁区块链API配置
    private $config = [
        'api_endpoint' => ' ', // 替换为实际API地址
      
        'timeout' => 30 // 请求超时时间(秒)
    ];

    /**
     * 上传数据到区块链
     * 
     * @param array $data 要上传的数据
     * @return array 返回结果
     */
    public function uploadToBlockchain($data) {
        // 验证数据
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException("数据必须是非空数组");
        }

        // 准备请求数据
        $requestData = [
            'app_id' => $this->config['app_id'],
            'timestamp' => time(),
            'version' => '1.0',
            'biz_content' => json_encode([
                'contract_address' => $this->config['contract_address'],
                'data' => $data
            ])
        ];

        // 生成签名
        $requestData['sign'] = $this->generateSignature($requestData);

        // 发送请求
        return $this->sendRequest('/gateway/blockchain/data/upload', $requestData);
    }

    /**
     * 生成请求签名
     * 
     * @param array $params 请求参数
     * @return string 签名
     */
    private function generateSignature($params) {
        // 按字典序排序参数
        ksort($params);

        // 拼接签名字符串
        $signString = '';
        foreach ($params as $key => $value) {
            if ($key != 'sign' && $value !== '' && !is_array($value)) {
                $signString .= $key . '=' . $value . '&';
            }
        }
        $signString = rtrim($signString, '&');

        // 使用App Key进行签名
        $signString .= $this->config['app_key'];
        
        // 使用SHA256算法生成签名
        return strtoupper(hash('sha256', $signString));
    }

    /**
     * 发送HTTP请求
     * 
     * @param string $path API路径
     * @param array $data 请求数据
     * @return array 响应数据
     */
    private function sendRequest($path, $data) {
        $url = $this->config['api_endpoint'] . $path;
        
        // 初始化cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // 执行请求
        $response = curl_exec($ch);
        
        // 检查错误
        if (curl_errno($ch)) {
            throw new RuntimeException('cURL请求错误: ' . curl_error($ch));
        }

        // 关闭连接
        curl_close($ch);

        // 解析响应
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('解析响应失败: ' . json_last_error_msg());
        }

        // 验证响应签名
        if (!$this->verifyResponseSignature($result)) {
            throw new RuntimeException('响应签名验证失败');
        }

        return $result;
    }

    /**
     * 验证响应签名
     * 
     * @param array $response 响应数据
     * @return bool 是否验证通过
     */
    private function verifyResponseSignature($response) {
        if (!isset($response['sign'])) {
            return false;
        }

        $sign = $response['sign'];
        unset($response['sign']);

        $expectedSign = $this->generateSignature($response);
        return $sign === $expectedSign;
    }
}


?>