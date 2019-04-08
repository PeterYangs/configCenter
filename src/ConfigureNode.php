<?php
/**
 * Date: 2019/4/3
 * Time: 10:01
 */

namespace Configure;

class ConfigureNode
{
    private $path;
    private $pullUrl;
    private $key;
    private $notifyUrl;
    private $data;

    /**
     * ConfigureNode constructor.
     * @param string $path 配置文件路径
     * @param string $pullUrl 拉取url
     * @param string $key 秘钥
     * @param string $notifyUrl 通知url
     */
    public function __construct($path, $pullUrl, $key, $notifyUrl)
    {
        $this->path = $path;
        $this->pullUrl = $pullUrl;
        $this->key = $key;
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * 拉取配置
     * @return bool|int
     */
    public function pull()
    {
        $data = $this->getResult($this->pullUrl, [
            'key' => $this->key,
            'version' => $this->getVersion()
        ]);

        if ($data) {
            $dataArr = json_decode($data, true);
            if ($dataArr['code'] != 2) {
                return false;
            }
            $this->notify();
            return $this->save($dataArr['data']['config'], $dataArr['data']['version']);
        }
        return false;
    }

    /**
     * 通知
     * @return bool|string
     */
    public function notify()
    {
        return $this->getResult($this->notifyUrl, [
            'key' => $this->key
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
     * @param string $key 键
     * @param string $defValue 默认值
     * @return string
     */
    public function get($key, $defValue = '')
    {
        $data = $this->getAll();
        if (empty($data) || !isset($data[$key])){
            return $defValue;
        }
        return $data[$key];
    }

    /**
     * 保存配置
     * @param array $data 数组配置
     * @param int $version 版本号
     * @return bool|int
     */
    private function save($data, $version = 0)
    {
        $configure = '';
        foreach ($data as $v) {
            if (empty($configure)) {
                $configure = "'{$v['key']}'" . '=>' . "'{$v['value']}'";
            } else {
                $configure .= ','.PHP_EOL . "    '{$v['key']}'" . '=>' . "'{$v['value']}'";
            }
        }
        if (empty($configure)){
            $configure = "    'version'" . '=>' . $version;
        } else {
            $configure .= ','.PHP_EOL . "    'version'" . '=>' . $version;
        }
        $str = <<<PHPDATA
<?php
return [
    {$configure}
];
PHPDATA;
        return file_put_contents($this->path, $str);
    }

    /**
     * 检查版本号，相同返回true,不同返回false
     * @param int $version 要检测的版本号
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

    /**
     * 获取所有配置
     * @return array
     */
    public function getAll(){
        $path = realpath($this->path);
        if (!file_exists($path)) {
            return [];
        }
        if (empty($this->data)){
            $this->data = require_once "{$path}";
        }
        return $this->data;
    }
}