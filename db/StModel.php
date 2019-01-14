<?php

namespace akaba\db;

use app\admin\model\Actlog;
use think\Db;
use think\Model;

class StModel extends Model
{
    protected $defaultDatas = []; //默认值,仅当对应键的值没有设置时,起作用
    public $addon = [
        'module' => '',
        'controller' => '',
        'action' => '',
    ];
    protected $defaultAttachments = []; //默认值, 仅当对应
    const PUBLIC_PATH = '/public/styles/%s/addons/%s/';//前端文件存储位置
    const DEFAULT_THEME = 'proDefault';//默认风格
    private $switcher = ['off', 'on'];//switcher状态
    public $lotteryDefault = [
        'start_music' => 'uploads/20180726/1532598136抽奖进行中.mp3',
        'award_music' => 'uploads/20180807/1533633901中奖提示音.mp3'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public static function init()
    {
        self::beforeInsert(function ($info) {
            $actlog = new Actlog();
            $insertData['data'] = input();
            $insertData['type'] = 1;
            $insertData['modelname'] = $info->name;
            $actlog->save($insertData);
        });
        self::afterUpdate(function ($info) {
            // 更新 查询缓存标识
            $actlog = new Actlog;
            $insertData['data'] = input();
            $insertData['type'] = 2;
            $insertData['modelname'] = $info->name;
            $actlog->save($insertData);
        });
        self::beforeDelete(function ($info) {
            // 更新 查询缓存标识
            $actlog = new Actlog();
            $insertData['data'] = $info->data;
            $insertData['type'] = 3;
            $insertData['modelname'] = $info->name;
            $actlog->save($insertData);
        });
    }

    public function getDefaultDatas()
    {
        return $this->defaultDatas;
    }

    public function getDefaultAttachments()
    {
        return $this->defaultAttachments;
    }

    public function switcherToNum($status)
    {
        if ($status != 'off') {
            $status = 'on';
        }
        return array_search($status, $this->switcher);
    }

    public function numToSwitcher($num = 0)
    {
        $num = intVal($num);
        return $this->switcher[$num];
    }

    protected function floatize($str, $precise = 2)
    {
        return round($str, $precise);
    }

    protected function orgDefaultData(array $data)
    {
        $defaultDatas = $this->getDefaultDatas();
        foreach ($defaultDatas as $key => $defautData) {
            if (!isset($data[$key], $key)) {
                $data[$key] = $defautData;
            }
        }
        return $data;
    }

    protected function orgData(array $data)
    {
        $data = $this->orgDefaultData($data);
        return $this->orgAttachment($data);
    }

    protected function orgAttachment(array $data)
    {
        $defaultAttachments = $this->getDefaultAttachments();
        foreach ($defaultAttachments as $key => $defaultAttachment) {
            if (empty($data[$key])) {
                if (\is_string($defaultAttachment)) {
                    $pair = [$defaultAttachment, self::DEFAULT_THEME];
                } else {
                    $themeKey = $defaultAttachment[1];
                    $themeValue = $data[$themeKey];
                    $pair = [$defaultAttachment[0], $themeValue];
                }
                $data[$key] = $this->loadAddonSrc($pair[0], $pair[1]);
            } else {
                $data[$key] = toImg3($data[$key]);
            }
        }
        return $data;
    }

    public function loadAddonSrc(string $src = '', string $style = self::DEFAULT_THEME)
    {
        if (empty($src)) {
            return $src;
        }
        preg_match('/^(https?:\/\/)/', $src, $match);
        if (!empty($match)) {
            return $src;
        }
        $this->getRelations();
        $src = preg_replace('/^\//', '', $src);
        $styledAddon = \sprintf(self::PUBLIC_PATH, $style, $this->addon['module']);
        return $this->domain . $styledAddon . $src;
    }

    private function getRelations()
    {
        $request = Request();
        $pathinfo = Request()->pathinfo();
        list($addons, $execute, $addonsInfo) = explode('/', $pathinfo);
        list($an_module, $an_controller, $an_action) = explode('-', $addonsInfo);
        $this->addon['module'] = $an_module;
        $this->addon['controller'] = $an_controller;
        $this->addon['action'] = $an_action;
        $this->domain = $request->domain();
    }

    public function unserialize($str, $whenEmpty = [])
    {
        if (is_string($str)) {
            $arr = '';
            if (is_serialized($str)) {
                $arr = \unserialize($str);
            }
            if (empty($arr)) {
                return $whenEmpty;
            }
            return $arr;
        }
        return $str;
    }

    public function arrayize($obj)
    {
        if (empty($obj)) {
            return [];
        } else {
            return $obj->toArray();
        }
    }

    public function orgResult($result)
    {
        $arr = $this->arrayize($result);
        return $this->orgData($arr);
    }

    public function actExists(int $actId, string $actIdKey = 'acid')
    {
        $exists = $this->where([$actIdKey => $actId])->value($actIdKey);
        if (empty($exists)) {
            return false;
        }
        return true;
    }

    public function saveDataByActId(int $actId, array $saveData, string $actIdKey = 'acid')
    {
        $exists = $this->actExists($actId, $actIdKey);
        if (empty($exists)) {
            $saveData[$actIdKey] = $actId;
            return $this->save($saveData);
        }
        return $this->save($saveData, [$actIdKey => $actId]);
    }

    public function saveDataByAcid($acid, $data, $acidKey = 'acid')
    { //已废弃,请优先使用saveDataByActId;
        return $this->saveDataByActId($acid, $data, $acidKey = 'acid');
    }

    public function getOriginDataByActId(int $actId, $fields = [], string $actIdKey = 'acid')
    {
        return $this->where([$actIdKey => $actId])->field($fields)->find();
    }

    public function getRawDataByActId(int $actId, $fields = [], string $actIdKey = 'acid')
    {
        $result = $this->getOriginDataByActId($actId, $fields, $actIdKey);
        return $this->orgResult($result);
    }

    public function removeSrcPrefixByFields(array $arr, $fields)
    {
        $tempArr = $arr;
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $field) {
            if (!empty($arr[$field])) {
                $tempArr[$field] = backImg($tempArr[$field]);
            }
        }
        return $tempArr;
    }

    public function addSrcPrefixByFields(array $arr, $fields)
    {
        $tempArr = $arr;
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $field) {
            if (!empty($tempArr[$field])) {
                $val = toImg3($tempArr[$field]);
                if (empty($val)) {
                    $val = '';
                }
                $tempArr[$field] = $val;
            }
        }
        return $tempArr;
    }

    public function getValue(array $where, string $field = "id"): string
    {
        return $this->where($where)->value($field);
    }

    public function getGlobalBg(int $actId)
    {
        $globalSetting = new \addons\setting\model\AddonsSetting;
        return $globalSetting->where('acid', $actId)->value("pc_bg_pic");
    }

    public function arrayData()
    {//试验性质,请谨慎使用
        if (empty($this)) {
            return [];
        }
        return $this->toArray();
    }
}