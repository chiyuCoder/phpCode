<?php
namespace akaba\serverfiles;

use OSS\Core\OssException;
use OSS\OssClient;
use \akaba\serverfiles\thirdparts;

class Aliyun extends thirdParts
{
    public $configName = 'aliyun_oss';
    public $cacheConfigKey = 'aliyun_config';
    public function __construct($uid)
    {
        parent::__construct($uid);
    }
    protected function build()
    {
        $this->manager = new OssClient($this->config['accesskeyid'], $this->config['accesskeysecret'], $this->config['endpoint'], false);
    }
    public function upload($file, $saveFile = null)
    {
        if (empty($saveFile)) {
            $saveFile = $this->getRelativePath($file);
        }
        try {
            $res = $this->manager->putObject($this->config['bucket'], $saveFile, file_get_contents($file));
            return $res;
        } catch (OssException $e) {
            $this->notice(-1, $e->getMessage());
        }
    }
}
