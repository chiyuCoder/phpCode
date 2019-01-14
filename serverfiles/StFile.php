<?php 

namespace akaba\serverfiles;
class StFile{
   
  
    public function saveImgData(string $path, string $str = '') {//$path不要加后缀
        $result = $this->parseData($str);
        if (!empty($result)) {
            switch($result['dataType']) {
                case 'base64':
                    $path = $this->saveBase64Data($path, $result['data'], $result['ext']);
                    break;
            }
        }   
        return $path;
    }
    public function parseData(string $str = '') {
        preg_match('/^\s*data:(\w+)\/(\w+);(\w+),(.+)$/ui', $str, $matches);
        $arr = [];
        if (!empty($matches)) {
            $arr['type'] = $matches[1];
            $arr['ext'] = $matches[2];
            $arr['dataType'] = $matches[3];
            $arr['data'] = $matches[4];
        }
        return $arr;
    }
    public function saveBase64Data($path, string $data, string $ext = 'jpg') {
        $fileData = base64_decode($data);
        $filename = $path.'.'.$ext;
        $this->createFileByData($filename, $fileData);        
        return $filename;
    }
    public function createFileByData(string $filename, $fileData) {
        $dir = pathinfo($filename)['dirname'];
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($filename, $fileData);
    }
    public function indexOf(string $haystack = '', string $needle = '') {
        $index = stripos($haystack, $needle);
        if ($index === false) {
            return -1;
        }
        return $index;
    }
}