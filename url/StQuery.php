<?php
namespace akaba\url;

class StQuery
{
    public function __construct($str = '')
    {
        $this->string = $str;
        if (!empty($str)) {
            $this->string = '?' . $this->removeMark($str);
            $this->array = $this->str2Arr($str);
        } else {
            $this->array = [];
        }
    }
    public function removeMark($str)
    {
        preg_match('/^\s*\??(.+)/', $str, $matches);
        return $matches[1];
    }
    public function str2Arr($str)
    {
        $str = $this->removeMark($str);
        $arr = [];
        $couples = explode('&', $str);
        foreach ($couples as $couple) {
            $group = explode('=', $couple);
            $len = sizeOf($group);
            if ($len == 2) {
                $arr[$group[0]] = urlDecode($group[1]);
            } else {
                array_push($arr, urlDecode($couple));
            }
        }
        return $arr;
    }
    public function arr2Str($arr)
    {
        $str = '?';
        $start = true;
        foreach ($arr as $key => $val) {
            if ($start) {
                $start = false;
            } else {
                $str .= '&';
            }
            $val = urlEncode($val);
            if (is_numeric($key)) {
                $str .= $val;
            } else {
                $str .= $key . '=' . $val;
            }
        }
        return $str;
    }
    public function generateArr($key, $val = '')
    {
        $arr = [];
        if (is_string($key)) {
            if (empty($val)) {
                array_push($arr, $key);
            } else {
                $arr[$key] = $val;
            }
        } else {
            $arr = $key;
        }
        return $arr;
    }
    public function add($key, $val = '')
    {
        $arr = $this->generateArr($key, $val);
        $arr = array_merge($this->array, $arr);
        $str = $this->arr2str($arr);
        return new StQuery($str);
    }
    public function replace($key, $val = '')
    {
        return $this->add($key, $val);
    }
    public function del($key)
    {
        $arr = $this->generateArr($key);
        $queryArr = $this->array;
        $queryKeys = array_keys($queryArr);
        foreach ($arr as $key) {
            if (in_array($key, $queryKeys)) {
                unset($queryArr[$key]);
            }
        }
        $str = $this->arr2str($queryArr);
        return new StQuery($str);
    }
    public function create($str)
    {
        if (is_array($str)) {
            $str = $this->arr2str($str);
        }
        return new StQuery($str);
    }
}
