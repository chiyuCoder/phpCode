<?php
    namespace akaba\serverfiles;

    abstract class thirdParts{
        public $configName = 'configName';
        public $cacheConfigKey = 'configKey';
        const ROOT_FLODER = '/'; 
        const IS_FIRST_WORD = 0;
        public function __construct($uid) {
            $this->userId = $uid;
            $this->configModel = new \app\admin\model\Config();
            $this->getConfig();
            $this->build();
        }
        abstract public function upload($file, $saveFile);
        abstract protected function build();
        // abstract protected function getConfig();
        public function notice($code = 1, $msg = 'ok') {
            die(json_encode(['code' => $code, 'msg' => $msg]));
        }
        private function getConfig() {
            // 
            $config = $this->configModel->where('name', $this->configName)->cache($this->cacheConfigKey)->value('value');
            if (empty($config)) {
                $this->notice(-1, '配置信息不存在');
            }
            $config = unserialize($config);
            if (empty($config)) {
                $this->notice(-1, '配置信息出错了');
            }
            $this->config = $config;
        }
        public function getRelativePath(string $path) {
            if (stripos($path, self::ROOT_FLODER) === self::IS_FIRST_WORD) {              
                $path = str_replace(ROOT_PATH,  '', $path);
            }
            return $path;
        }
    }