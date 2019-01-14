<?php
namespace akaba\dev;

class StTraverse{
    public $dirs = [
        "D:/myprocedure/wamp/www/application/admin/controller"
    ];
    public function travelAll() {
        $dirs = $this->dirs;
        foreach($dirs as $dir) {
            $this->travelDir($dir);
        }
    }
    public function travelDir(string $dir) {
        $allPhps = glob($dir."/*.php");
        $logs = [];
        foreach($allPhps as $allPhp) {
            $log = $this->parsePhp($allPhp);
            array_push($logs, $log);
        }
        file_put_contents(__DIR__."/parsePhp.json", json_encode($logs, JSON_PRETTY_PRINT));
    }    
    public function parsePhp(string $php) {
        $text = file_get_contents($php);
        $map = [
            "path" => $php,
            "namespace" => "",
            "class" => "",
            "extends" => "",
            "publicFuncs" => [],
        ];
        preg_match("/namespace\s+([\w\/\\\\]+)/", $text, $matches);
        if (!empty($matches)) {
            $map["namespace"] = $matches[1];
        }
        preg_match("/class\s+(\w+)(?:\s+extends\s+(\w+))*/i" , $text, $matches);
        if (!empty($matches)) {
            $map["class"] = $matches[1];
            if (!empty($matches[2])) {
                $map["extends"] = $matches[2];
            }
            $map['publicFuncs'] = $this->travelPublicFuncs($text);
        }
        return $map;
    }
    public function travelPublicFuncs(string $text) {
        $funcs = [];
        preg_match_all("/^(?:\s*public)*\s+function\s+([\w\_]+)\([^\)]*\)/Um", $text, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            $func = [
                "function" => $match[1]
            ];
            array_push($funcs, $func);
        }
        return $funcs;
    }
}

$stTraverser = new StTraverse();
$stTraverser->travelAll();