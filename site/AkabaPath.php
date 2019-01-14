<?php
namespace akaba\site;


class AkabaPath{
    public $path;
    private $root;
    private $posix;
    private $windows;
    const PARENT_NODE = '..';
    const SIBLING_NODE = '.';
    static function convertToPosix($path) {
        $path = trim($path);
        return str_replace('\\', '/', $path);
    }
    static function convertToWindows($path) {
        $path = trim($path);
        return str_replace('/', '\\', $path);
    }
    static function getRoot() {
        if (PHP_OS == "WINNT") {//windows[wind10 only??]
            $path = self::convertToPosix(__DIR__);
            $rootDelimiter = ":/";
            $position = strpos($path, $rootDelimiter);
            return substr($path, 0, $position) + $rootDelimiter;
        } else {
            return '/';
        }
    }  
    static function isAbsolute($path) {
        $path = trim($path);
        $firstLetter = substr($path, 0, 1);
        if ($firstLetter === '/') {
            return true;
        }
        preg_match('/^[A-Za-z]+\:/u', $path, $match);//https://     
        return !!$match;
    }
    static function join($parent, $child) {
        if (self::isAbsolute($child)) {
            return $child;
        }
        $parent = self::convertToPosix($parent);
        $parent = preg_replace('/\/$/', '', $parent);
        $child = self::convertToPosix($child);
        $path = $parent.'/'.$child;
        return self::commonize($path);    
    }
    static function commonize($path) {
        $path = self::convertToPosix($path);
        $arr = explode('/', $path);
        $reOrgPathArr = [];
        foreach($arr as $index => $nodeName) {
            if (empty($index) && empty($nodeName)) {
                array_push($reOrgPathArr, $nodeName);
                continue;
            } 
            if ($nodeName == self::PARENT_NODE) {
                if (empty($reOrgPathArr)) {
                    array_push($reOrgPathArr, $nodeName);
                    continue;
                }
                $nodeNum = sizeOf($reOrgPathArr);
                $lastNodeIndex =  $nodeNum - 1;
                $lastNode = $reOrgPathArr[$lastNodeIndex];
                if ($lastNode == self::PARENT_NODE) {
                    array_push($reOrgPathArr, $nodeName);
                    continue;
                }
                unset($reOrgPathArr[$lastNodeIndex]);
                continue;
            }
            if ($nodeName == self::SIBLING_NODE) {
                continue;
            }
            array_push($reOrgPathArr, $nodeName);
        }
        return implode('/', $reOrgPathArr);
    }
    static function relative($a, $b) {
        $a = self::commonize($a);
        $b = self::commonize($b);
        $arrA = explode("/", $a);
        $arrB = explode("/", $b);
        $common = [];
        $stopIndex = 0;
        $bLevelNum = sizeOf($arrB);
        foreach ($arrA as $level => $aNode) {
            if ($bLevelNum <= $level) {
                break;
            } else {
                $bNode = $arrB[$level];
                if ($aNode === $bNode) {
                    if (empty($common)) {
                        array_push($common, self::SIBLING_NODE);
                    }
                    continue;
                } else {
                    $stopIndex = $level;
                    break;
                }
            }
        }
        $leftNumOfA = sizeof($arrA) - $stopIndex;
        while($leftNumOfA > 0) {
            array_push($common, self::PARENT_NODE);
            $leftNumOfA --;
        }
        $leftOfB = array_slice($arrB, $stopIndex);
        $common = array_merge($common, $leftOfB);
        $path = implode("/", $common);
        return self::commonize($path);
    }
    public function __construct($path = '') {
        if (empty($path)) {
            $path = __DIR__;
        }
        $this->path = self::commonize($path);
    }
    public function __get($name) {
        if ($name == 'root') {
            if (empty($this->root)) {
                $this->root = self::getRoot();
            }
            return $this->root;
        }
        if ($name == "posix") {
            if (empty($this->posix)) {
                $this->posix = self::convertToPosix($this->path);
            }
            return $this->posix;
        }
        if ($name == "windows") {
            if (empty($this->posix)) {
                $this->posix = self::convertToWindows($this->path);
            }
            return $this->posix;
        }
    }
    public function add($path) {
        return self::join($this->path, $path);
    }
    public function relateTo($path) {
        return self::relative($this->path, $path);
    }
}


