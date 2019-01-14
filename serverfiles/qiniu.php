<?php
namespace akaba\serverfiles;
use  \akaba\serverfiles\thirdparts;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
class QiNiu extends thirdParts
{
    public $configName = 'qiniu_oss';
    public $cacheConfigKey = 'qiniu_config';
    public function __construct($uid)
    {
        parent::__construct($uid);
    }
    protected function build()
    {
        $config = $this->config;
        $this->origin = $config['url'];
        $this->accessKey = $config['accesskey'];
        $this->secretKey = $config['secretkey'];
        $this->bucket = $config['bucket'];
        $this->manager = new Auth($this->accessKey, $this->secretKey);
        $this->token = $this->manager->uploadToken($this->bucket);
    }
    public function upload($file, $saveFile = null){
        $uploadMgr = new UploadManager();
        if (empty($saveFile)) {
            $saveFile = $this->getRelativePath($file);
        }
        list($ret, $err) = $uploadMgr->putFile($this->token, $saveFile, $file);
        if ($err !== null) {
            $this->notice(-1, $err);
        } else {
            return $ret;
        }
    }
   
}
