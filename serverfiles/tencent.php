<?php
namespace akaba\serverfiles;

use \akaba\serverfiles\thirdparts;
use Qcloud\Cos\Client;
class Tencent extends thirdParts
{
    public $configName = 'qcloud_cos';
    public $cacheConfigKey = 'qclound_config';
    public function __construct($uid)
    {
        parent::__construct($uid);
    }
    protected function build()
    {
        $this->manager = new Client([
            'region' => $this->config['region'],
            'credentials'=> [
                'secretId'    => $this->config['secretid'],
                'secretKey' => $this->config['secretkey']
            ]
        ]);
    }
    public function upload($file, $saveFile = null){
        if (empty($saveFile)) {
            $saveFile = $this->getRelativePath($file);
        }
        try {
            $result = $this->manager->putObject([
                //bucket的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
                'Bucket' => $this->config['bucket'],
                'Key' => $saveFile,
                'Body' => file_get_contents($file)
            ]);
            $result = $result->toArray();
            return $result;
        } catch(\Exception $e) {
            $this->notice(-1, $e->getMessage());
        }
    }
}
