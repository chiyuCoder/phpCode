<?php
namespace akaba\url;
use akaba\url\StQuery;
class StLink
{
    public $protocol = '';
    public $host = '';
    public $path = '';
    public $query = '';
    public $queryObj;
    const IS_BEGIN = 0;
    const SIBLING_FLODER_MARK = './';
    const PARENT_FLODER_MARK = '../';
    const ROOT_MARK = '/';
    public function __construct($url = null)
    {
        $this->local = [];
        $this->localUrl = $this->getLocalUrl();
        $this->givenUrl = trim($url);
        $this->parseUrl();
        $this->linkQueryObj();
    }
    private function linkQueryObj() {
        $queryStr = $this->query;
        $this->queryObj = new StQuery($queryStr);
        $this->queryArray = &$this->queryObj->array; 
        $this->query = &$this->queryObj->string; 
    }
    private function getLocalUrl()
    {
        $isHttps = $this->is_https();
        $protocol = '';
        if ($isHttps) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $this->local['protocol'] = 'http://';
        $this->local['host'] = $_SERVER['SERVER_NAME'];
        if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
            $this->local['path'] = strstr($_SERVER['REQUEST_URI'], '?', true);
            $this->local['query'] = strstr($_SERVER['REQUEST_URI'], '?');
        } else {
            $this->local['path'] = $_SERVER['REQUEST_URI'];
            $this->local['query'] = '';
        }
        return $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    private function is_https()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }
    private function parseUrl()
    {
        if (empty($this->givenUrl)) {
            $url = $this->localUrl;
        } else {
            $url = $this->givenUrl;
            if (strpos($url, './') === self::IS_BEGIN) {
                $uri = $this->joinPath($this->local['path'], $url);
                $url = $this->local['protocol'].$this->local['host'].$uri;
            } else if (strpos($url, '../') === self::IS_BEGIN) {
                $uri = $this->joinPath($this->local['path'], $url);
                $url = $this->local['protocol'].$this->local['host'].$uri;      
            }
        }
        $this->parseAbsoluteUrl($url);       
    }
    public function joinPath($parent, $child) {
        
        if (
            strpos($child, "https://") ===  self::IS_BEGIN
            || strpos($child, "http://") ===  self::IS_BEGIN 
            || strpos($child, "/") ===  self::IS_BEGIN
        ) {
            return $child;
        } else {
            if (strripos($parent, '/') != strLen($parent) - 1) {
                $parent = $parent.'/';
            }
            $uri = $this->getTruePath($parent.$child);           
        }
        return $parent.$child; 
    }
    public function getTruePath($uri) {
        $arr = explode('/', $uri);
        $paths = [];
        $arrSize = sizeOf($arr);
        $path = $arr[0];
        $dec = 0;
        for ($i = 1; $i < $arrSize; $i ++) {
            if ($arr[$i] == '.') {
                $dec += 1;
                $path = $paths[$i - $dec];
            } elseif ($arr[$i] == '..') {
                $dec += 2;
                $path = $paths[$i - $dec];
            } else {   
                $path .= '/'.$arr[$i];
            }
            $paths[$i] = $path;
        }
        $fullPath = $paths[$arrSize - 1];
        return $fullPath;                                                                                        
    }
    private function parseAbsoluteUrl($url) {
        preg_match('/^(https*:\/\/)?([\w\.]+)?(\/[^?]+)?(\?.*)?$/', $url, $matches);
        if (is_array($matches)) {
            if (!empty($matches[1])) {
                $this->protocol = $matches[1];
            } else {
                $this->protocol = $this->local['protocol'];
            }
            if (!empty($matches[2])) {
                $this->host = $matches[2];
            } else {
                $this->host = $this->local['host'];
            }
            if (!empty($matches[3])) {
                $this->path = $matches[3];
            } else {
                $this->path = '';
            }
            if (!empty($matches[4])) {
                $this->query = $matches[4];
            } else {
                $this->query = '';
            }
        }
        $this->href = $this->protocol.$this->host.$this->path.$this->query;
    }
    public function addQuery($key, $val = '')
    {
        $str = $this->queryObj->add($key, $val)->string;
        return $this->makeNewUrl($str, 'query');
    }
    public function changeQuery($key) {
        $str = $this->queryObj->create($key)->string;
        return $this->makeNewUrl($str, 'query');
    }
    public function delQuery($key)
    {
        $str = $this->queryObj->del($key)->string;
        return $this->makeNewUrl($str, 'query');
    }
    public function makeNewUrl($str, $position = 'query')
    {
        $newUrl = [];
        $newUrl['protocol'] = $this->protocol;
        $newUrl['host'] = $this->host;
        $newUrl['path'] = $this->path;
        $newUrl['query'] = $this->query;
        $newUrl[$position] = $str;
        $newUrlStr = $newUrl['protocol'] . $newUrl['host'] . $newUrl['path'] . $newUrl['query'];
        return new StUrl($newUrlStr);
    }
}
