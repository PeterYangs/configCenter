<?php
/**
 * Date: 2019/4/3
 * Time: 10:01
 */

namespace Configure;

class ConfigureNode
{
    private $path;
    private static $obj;

    private function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * 获取实例
     * @param $path
     * @return ConfigureNode
     */
    public static function getInstance($path)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self($path);
        }
        return self::$obj;
    }

    /**
     * 拉取配置
     * @param $url 获取配置url
     * @param $key key
     * @param $notifyUrl 通知url
     * @return bool|int
     */
    public function pull($url, $key, $notifyUrl)
    {
        $data = $this->getResult($url, [
            'key' => $key,
            'version' => $this->getVersion()
        ]);

        if ($data) {
            $dataArr = json_decode($data, true);
            if ($dataArr['code'] != 2) {
                return false;
            }
            $this->notify($notifyUrl, $key);
            return $this->save($dataArr['data']['config'], $dataArr['data']['version']);
        }
        return false;
    }

    /**
     * 通知
     * @param $url
     * @param $key
     * @return bool|string
     */
    public function notify($url, $key)
    {
        return $this->getResult($url, [
            'key' => $key
        ]);
    }

    /**
     * 发送一个请求
     * @param $url
     * @param array $data
     * @return bool|string
     */
    private function getResult($url, $data = [])
    {
        $data = http_build_query($data);
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencodedrn" .
                    "Content-Length: " . strlen($data) . "rn",
                'content' => $data
            )
        );
        $context = stream_context_create($opts);
        return file_get_contents($url, false, $context);
    }

    /**
     * 读取配置
     * @param $key 键
     * @param string $defValue 默认值
     * @return string
     */
    public function get($key, $defValue = '')
    {
        if (!file_exists($this->path)) {
            return $defValue;
        }
        $data = file_get_contents($this->path);
        $dataArr = explode(PHP_EOL, $data);
        if (empty($dataArr)) {
            return $defValue;
        }
        foreach ($dataArr as $v) {
            if (preg_match('/^' . $key . '=/i', trim($v))) {
                $vArr = explode('=', $v);
                if (count($vArr) == 2) {
                    return $vArr[1];
                }
            }
        }
        return $defValue;
    }

    /**
     * 保存配置
     * @param $data 数组配置
     * @param $version 版本号
     * @return bool|int
     */
    private function save($data, $version = 0)
    {
        $configure = '';
        foreach ($data as $v) {
            if (empty($configure)) {
                $configure = $v['key'] . '=' . $v['value'];
            } else {
                $configure .= PHP_EOL . $v['key'] . '=' . $v['value'];
            }
        }
        $configure .= PHP_EOL . 'version' . '=' . $version;
        return file_put_contents($this->path, $configure);
    }

    /**
     * 检查版本号，相同返回true,不同返回false
     * @param $version 要检测的版本号
     * @return bool
     */
    public function checkVersion($version)
    {
        if ($this->getVersion() == $version) {
            return true;
        }
        return false;
    }

    /**
     * 获取本地版本
     * @return string
     */
    public function getVersion()
    {
        return $this->get('version', 0);
    }
}