<?php
namespace akaba\serverfiles;

class uploadFiles
{
    public $limitUnit = 'm'; //上传文件的最大大小的单位
    public $limitSize = 2; //上传文件的最大大小的数值
    private $servers = [ //上传服务器列表
        '0' => 'local',
        '1' => 'qiniu',
        '2' => 'tencent',
        '3' => 'ali',
    ];
    private $serverClass = [
        'qiniu' => 'qiniu',
        'tencent' => 'tencent',
        'ali' => 'aliyun',
    ];
    private $defaultServer = 'qiniu';
    private $byteSizes = [
        'k' => 1024,
        'm' => 1048576,
        'g' => 1073741824,
    ];
    public $nowServer = 'qiniu'; //当前附件服务器
    public $acceptTypes = [ //接收类型
        'audio' => [
            'mp3',
        ],
        'image' => [
            'png',
            'jpeg',
            'jpg',
        ],
    ];
    private $source = [ //当前上传文件信息
        'name' => 'string',
        'size' => 'int',
        'type' => 'mime',
        'typePrefix' => 'string',
        'typeSuffix' => 'string',
        'error' => 'int',
        'tmp_name' => 'string',
    ];
    private $pageTypes = ["admin", "wap", "app"];
    private function buildAdminServer() {
        if (empty($uid)) {
            $uid = session('manager.account');
            $this->userId = $uid;
        }
        $this->nowServer = $this->getServerByUserId();
    }
    private function buildAppServer() {
        $this->actId = encrypt($_REQUEST['acid'], 'D');
        $this->nowServer = $this->getServerByActId();
    }
    public function __construct($pageType = 'admin', $uid = '')
    {
        //为了兼容==>
        if (!in_array($pageType, $this->pageTypes)) {
            $pageType = "admin";
            $uid = $pageType;
        }
        //为了兼容==>
        switch (strToLower($pageType)) {
            case 'admin':
                $this->buildAdminServer();
                break;
            case 'wap':
            case 'app':
                $this->buildAppServer();
                break;
        }
        $this->setLimitUnit($this->limitUnit);
        $this->maxSize = $this->getMaxByteSize();
        $this->logger = new \app\admin\model\Image();
    }
    private function getMaxByteSize()
    {
        return $this->limitSize * $this->byteSizes[$this->limitUnit];
    }
    private function setLimitUnit($unit)
    {
        $unit = strToLower($unit);
        $relations = [
            'm' => 'm',
            'mb' => 'm',
            'mbyte' => 'm',
            'k' => 'k',
            'kb' => 'k',
            'kbyte' => 'k',
        ];
        if (!isset($relations[$unit])) {
            $this->limitUnit = $relations['m'];
        } else {
            $this->limitUnit = $relations[$unit];
        }
    }
    private $uploadFile = [
        'name' => 'string',
        'typePrefix' => 'audio',
        'typeSuffix' => 'mp3',
        'type' => 'audio/mp3',
        'tmp_name' => 'string',
        'size' => 'int',
        'error' => 'int',
    ];
    public function getServerByActId($actId = 0) {
        $model = new \app\admin\model\Active();
        if (empty($actId)) {
            $actId = $this->actId;
        }
        $serverIndex = $model->where(['id'=>$actId])->value('cloud');
        if (empty($serverIndex)) {
            $serverIndex = 1;
        }
        $this->serverIndex = $serverIndex;
        return $this->getServerByIndex($serverIndex);
    }
    public function getServerByIndex($serverIndex) {
        $server = $this->servers[$serverIndex];
        if (!isset($server)) {
            $server = $this->defaultServer;
        }
        return $server;
    }
    private function getServerByUserId()
    {
        $settingTable = new \app\admin\model\UserSettings();
        $serverIndex = $settingTable->where(['uid' => $this->userId])->value('uploader');
        if (empty($serverIndex)) {
            $serverIndex = 1;
        }
        $this->serverIndex = $serverIndex;
        return $this->getServerByIndex($serverIndex);
    }
    private function getFileInfo($file)
    {
        $arr = $file;
        list($prefix, $suffix) = explode('/', $file['type']);
        $arr['typePrefix'] = $prefix;
        $arr['typeSuffix'] = $suffix;
        return $arr;
    }
    private function checkType()
    {
        $acceptTypes = $this->acceptTypes;
        if (empty($acceptTypes)) {
            return true;
        }
        $acceptKeys = array_keys($acceptTypes);
        if (in_array('*', $acceptKeys) || in_array('all', $acceptKeys)) {
            return true;
        }
        $key = $this->source['typePrefix'];
        $val = $this->source['typeSuffix'];
        if (!in_array($key, $acceptKeys)) {
            $this->stErrMsg(-1, $key . '不受支持');
        }
        $typeArr = $acceptTypes[$key];
        if (in_array('*', $typeArr) || in_array('all', $typeArr)) {
            return true;
        }
        if (!in_array($val, $typeArr)) {
            $this->stErrMsg(-1, $key . '/' . $val . '不受支持');
        }
    }
    private function checkSize()
    {
        $maxSize = $this->getMaxByteSize();
        if (!empty($maxSize)) {
            $sourceSize = $this->source['size'];
            if ($sourceSize > $maxSize) {
                $this->stErrMsg(-1, '文件不能超过' . $this->limitSize . $this->limitUnit);
            }
        }
    }
    private function isSuitable()
    {
        $suitType = $this->checkType();
        $beyondSize = $this->checkSize();
    }
    public function setLimitSize($limitSize, $limitUnit = 'm')
    {
        if (is_numeric($limitSize)) {
            $this->limitSize = $limitSize;
            $this->setLimitUnit($limitUnit);
        }
    }
    public function setAcceptType($accept)
    {
        if (!empty($accept)) {
            if (is_string($accept)) {
                $this->acceptTypes = [];
                $pairs = explode(',', $accept);
                foreach ($pairs as $pair) {
                    list($key, $val) = explode('/', $pair);
                    $key = trim($key);
                    if (empty($val)) {
                        $val = '*';
                    }
                    $val = trim($val);
                    if (!isset($this->acceptTypes[$key])) {
                        $this->acceptTypes[$key] = [];
                    }
                    array_push($this->acceptTypes[$key], $val);
                }
            } elseif (is_array($accept)) {
                $this->acceptTypes = $accept;
            }
        }
    }
    public function upload($file)
    { //上传文件接口
        if (empty($file)) {
            $this->stErrMsg(-1, '上传文件不能为空');
        }
        $this->source = $this->getFileInfo($file);
        $this->isSuitable();
        $this->uploadToServer();
    }
    public function changeIOSImg(string $filename)
    {//旋转IOS图片
        $fileInfo = pathinfo($filename);
        $ext = $fileInfo['extension'];
        if ($ext == 'jpg' || $ext == 'jpeg') {
            try {
                $source = imageCreateFromString(file_get_contents($filename));
                $exif = exif_read_data($filename);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 8:
                        $dest = imageRotate($source, 90, 0);
                        break;
                        case 3:
                        $dest = imageRotate($source, 180, 0);
                        break;
                        case 6:
                        $dest = imageRotate($source, -90, 0);
                        break;
                    }
                    if ($dest) {
                        $destFile = $fileInfo['dirname'].'/'.$fileInfo['filename'].'.rotated.jpg';
                        imageJpeg($dest, $destFile);
                        $fileInfo = pathinfo($destFile);
                    }
                }
            } catch (\Exception $e) {
                $errLog = $fileInfo['dirname'].'/'.date('Y-m-d').'.err.log';
                $errDir = pathinfo($errLog)['dirname'];
                if (!is_dir($errDir)) {
                    mkdir($errDir, 0777, true);
                }
                file_put_contents($errLog, $filename.'重写失败,因为'.$e->getMessage().PHP_EOL.PHP_EOL);
            }
        }

        return ['path' => $fileInfo['dirname'].'/', 'name' => $fileInfo['basename']];
    }
    private function uploadToServer()
    {
        $uploadResult = $this->uploadToLocal($this->source);
        $filename = $uploadResult['path'] . $uploadResult['name'];
        $localPath = $this->remoteLocal($filename);
        $result = $this->unifyOutPut($localPath, $this->nowServer);
        $this->logUpload($filename);
        $result['itemId'] = $this->logger->getLastInsID();
        if (empty($result)) {
            $result['url'] = '/uploads/' . date('Ymd/') . $uploadResult['name'];
            $result['server'] = 'local';
        }
        $this->StErrMsg(1, $result);
    }
    public function remoteLocal(string $localPath) {
        switch ($this->nowServer) {
            case 'qiniu':
                $result = $this->uploadToQiNiu($localPath);
                break;
            case 'ali':
                $result = $this->uploadToAliyun($localPath);
                break;
            case 'tencent':
                $result = $this->uploadToTencent($localPath);
                break;
        }
        return $result;
    }
    public function unifyOutPut($var, $server = '')
    {
        if (empty($server)) {
            $server = $this->nowServer;
        }
        if (empty($var)) {
            return [];
        }
        $outPut = [
            'url' => '',
            'server' => $server,
        ];
        switch ($server) {
            case 'qiniu':
                $outPut['url'] = $this->uploader->origin . '/' . $var['key'];
                break;
            case 'ali':
                $outPut['url'] = $var['info']['url'];
                break;
            case 'tencent':
                $outPut['url'] = $var['ObjectURL'];
                break;
            default:
                $outPut['url'] = '/';
                break;
        }
        return $outPut;
    }
    private function getSaveFilename($uploadFile, $saveFile = null)
    {
        if (empty($saveFile)) {
            $path = ROOT_PATH . 'uploads/' . date('Ymd/');
            $name = time() . $uploadFile['name'];
        } else {
            $pathInfo = pathInfo($saveFile);
            $path = $pathInfo['dirname'];
            $name = $pathInfo['basename'];
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return ['path' => $path, 'name' => $name];
    }
    public function uploadToQiNiu($filename, $savefile = '')
    {
        $this->uploader = new \akaba\serverfiles\qiniu($this->userId);
        return $this->uploader->upload($filename, $savefile);
    }
    public function uploadToTencent($filename, $savefile = '')
    {
        $this->uploader = new \akaba\serverfiles\tencent($this->userId);
        return $this->uploader->upload($filename, $savefile);
    }
    public function uploadToAliyun($filename, $savefile = '')
    {
        $this->uploader = new \akaba\serverfiles\aliyun($this->userId);
        return $this->uploader->upload($filename, $savefile);
    }
    public function getUniPath($basename, $date = 0) {
        $basename = pathinfo($basename)['basename'];
        if (empty($date)) {
            $date = date('Ymd');
            $basename = time().$basename;
        }
        return '/uploads/'.$date.'/'.$basename;
    }
    public function logUpload($filename, $fileType = '')
    {//
        $filename = pathinfo($filename)['basename'];
        if (empty($fileType)) {
            $fileType = $this->source['typePrefix'];
        }
        $this->logger->add($filename, date('Ymd'), $this->serverIndex, $fileType);
    }
    public function uploadToLocal(array $uploadFile, $saveFile = '')
    {
        $saveFilename = $this->getSaveFilename($uploadFile, $saveFile);
        $path = $saveFilename['path'];
        $name = $saveFilename['name'];
        if (move_uploaded_file($uploadFile['tmp_name'], $path . '/' . $name)) {
            return ['path' => $path, 'name' => $name];
        } else {
            $this->stErrMsg(-1, '上传本地出错');
        }
    }
    public function stErrMsg($code = 1, $msg = 'ok')
    {
        $arr = ['code' => $code, 'msg' => $msg];
        die(json_encode($arr));
    }
}
