<?php
namespace akaba\site;

class StPath
{
    const PARENT_NODE = "..";
    const SIBLING_NODE = ".";
    public function __construct(string $path = '')
    {
        $this->os = strToLower(PHP_OS);
        $this->delimiter = $this->getDelimiterByOs();
        if (empty($path)) {
            $path = ROOT_PATH;
        } else {
            $path = \preg_replace("/(\/|\\\\)+$/", '', $path);
        }
        $this->path = str_replace("\\", "/", $path);
        $this->root = $this->path;
    }
    function getDelimiterByOs(string $os = '') {
        if (empty($os)) {
            $os = $this->os;
        }
        $delimiter = '/';
        if (strToLower($os) == 'windows') {
            $delimiter = "\\";
        }
        return $delimiter;
    }
    public function join($a, $b = "./")
    {
        if ($this->isAbsPath($b)) {
            return $b;
        }
        $arrA = explode($this->delimiter, $a);
        $arrB = explode($this->delimiter, $b);
        $arr = array_merge($arrA, $arrB);
        $arrLen = sizeOf($arr);
        $pathTravel = [];
        $path = '';
        $searchProcessArray = [];
        for ($nowIndex = 0; $nowIndex < $arrLen; $nowIndex++) {
            $nodeName = $arr[$nowIndex];
            if ($nodeName == self::PARENT_NODE) {
                $pathLevel = sizeOf($pathTravel);
                $parentNode = $pathTravel[$pathLevel - 1];
                if (!isset($parentNode) || $parentNode == self::PARENT_NODE) {
                    array_push($pathTravel, self::PARENT_NODE);
                } else {
                    unset($pathTravel[$pathLevel - 1]);
                }
            } elseif ($nodeName == self::SIBLING_NODE) {
                continue;
            } else {
                if ($nodeName !== '') {
                    array_push($pathTravel, $nodeName);
                } elseif (!$nowIndex) {
                    array_push($pathTravel, $nodeName);
                }
            }
        }
        return implode($this->delimiter, $pathTravel);
    }
    public function isAbsPath(string $path)
    {
        $firstLetterIndex = 0;
        if ($this->os == 'linux') {
            $rootPath = '\/';
        } else {
            $rootPath = "\w:\\\\";
        }
        preg_match("/^" . $rootPath . "/", $path, $matches);
        if (empty($matches)) {
            return false;
        } else {
            return true;
        }
    }
}
