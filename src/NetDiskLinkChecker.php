<?php
namespace eking\netdisk;

class LinkChecker
{
    const VERSION = "1.0.0";

    protected $url = '';


    public function __construct()
    {
        // 初始化操作，如果需要的话
    }
    /**
     * 检查阿里云盘分享链接是否有效
     *
     * @param string $url 阿里云盘分享链接
     * @param string|null $pwd 分享链接的密码（可选）
     * @return bool 返回链接是否有效
     */
    public function aliYunCheck($url)
    {
        // 从URL中提取分享ID
        $share_id = substr($url, 30);

        // 构建获取分享信息的API URL
        $url = "https://api.aliyundrive.com/adrive/v3/share_link/get_share_by_anonymous?share_id={$share_id}";

        // 设置请求头，指定Referer
        $headers = [
            'Referer: https://www.aliyundrive.com/',
        ];

        // 发起GET请求获取分享信息
        list($success, $response) = $this->get($url, $headers);

        // 如果请求成功
        if ($success) {
            // 解析返回的JSON数据
            $r = json_decode($response, true);
            // 返回是否存在错误码（即链接是否有效）
            return empty($r['code']);
        }

        // 如果请求失败，返回false
        return false;
    }
    /**
     * 检查百度网盘分享链接是否有效
     *
     * @param string $url 百度网盘分享链接
     * @return bool 返回链接是否有效
     */
    public function baiduYunCheck($url)
    {
        // 初始化 cURL 会话
        $ch = curl_init();

        // 设置 cURL 选项，包括要访问的 URL、返回传输结果、不包含头部信息、不跟随重定向、设置用户代理
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1 Edg/94.0.4606.81");

        // 执行 cURL 请求并获取响应
        $response = curl_exec($ch);

        // 获取 HTTP 状态码
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 获取重定向的 URL
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        // 关闭 cURL 会话
        curl_close($ch);

        // 如果 HTTP 状态码为 200，表示请求成功
        if ($httpCode == 200) {
            // 检测链接是否失效，通过查找重定向 URL 中是否包含 "error" 字符串
            $errorIndex = strpos($redirectUrl, "error");
            return $errorIndex === false;
        }

        // 如果 HTTP 状态码不是 200，返回 false，表示链接无效
        return false;
    }

    /**
     * 检查 115 网盘分享链接是否有效
     *
     * @param string $url 115 网盘分享链接
     * @return bool 返回链接是否有效
     */
    public function d115check($url)
    {
        // 从 URL 中提取分享码
        $shareCode = substr($url, 18);

        // 初始化 cURL 会话
        $ch = curl_init();

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, "https://webapi.115.com/share/snap?share_code={$shareCode}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // 执行 cURL 请求并获取响应
        $response = curl_exec($ch);

        // 关闭 cURL 会话
        curl_close($ch);

        // 如果响应为空或 false，返回 false
        if ($response === false) {
            return false;
        }

        // 检查响应中是否包含错误码 4100012
        $errorIndex = strpos($response, '"errno":4100012');
        return $errorIndex!== false;
    }

    /**
     * 检查夸克网盘分享链接是否有效
     *
     * @param string $url 夸克网盘分享链接
     * @return bool 返回链接是否有效
     */
    public function quarkCheck($url)
    {
        // 使用正则表达式从URL中提取分享ID
        preg_match('/https:\/\/pan\.quark\.cn\/s\/(\w+)[\?]?/', $url, $matches);
        // 如果没有匹配到分享ID，则返回false
        if (!$matches) {
            return false;
        }
        // 提取提取码
        $pwd_id = $matches[1];

        // 构建获取分享信息的API URL
        $url = "https://pan.quark.cn/1/clouddrive/share/sharepage/token";
        // 设置请求头，指定Referer
        $headers = [
            'Referer: https://pan.quark.cn',
        ];
        // 发起POST请求获取分享信息
        list($success, $response) = $this->post($url, $headers, ['pwd_id' => $pwd_id]);
        // 如果请求成功
        if ($success) {
            // 解析返回的JSON数据
            $r = json_decode($response, true);
            // 返回是否存在错误码（即链接是否有效）
            return $r['code'] == 0 || $r['code'] == 41008;
        }
        // 如果请求失败，返回false
        return false;
    }


    /**
     * 检查给定的URL是否属于支持的云存储服务，并调用相应的检查方法
     *
     * @param string $url 要检查的URL
     * @return bool 如果URL属于支持的云存储服务并且有效，则返回true；否则返回false
     */
    public function checkUrl($url)
    {
        // 检查URL中是否包含阿里云盘的域名
        if (strpos($url, 'aliyundrive.com')!== false) {
            // 如果是阿里云盘的URL，调用aliYunCheck方法进行检查
            return $this->aliYunCheck($url);
        } 
        // 检查URL中是否包含115网盘的域名
        elseif (strpos($url, '115.com')!== false) {
            // 如果是115网盘的URL，调用d115check方法进行检查
            return $this->d115check($url);
        } 
        // 检查URL中是否包含夸克网盘的域名
        elseif (strpos($url, 'quark.cn')!== false) {
            // 如果是夸克网盘的URL，调用quarkCheck方法进行检查
            return $this->quarkCheck($url);
        } 
        // 检查URL中是否包含百度网盘的域名
        elseif (strpos($url, 'baidu.com')!== false) {
            // 如果是百度网盘的URL，调用baiduyunCheck方法进行检查
            return $this->baiduyunCheck($url);
        }
        // 如果URL不属于上述任何支持的云存储服务，返回false
        else{
            return false;
        }

    }
}

// 使用示例
// $checker = new NetDiskLinkChecker();
// $checker->checkUrl('https://www.aliyundrive.com/s/someshareid');
