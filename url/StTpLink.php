<?php

namespace akaba\url;

use akaba\url\StLink;

class StTpLink extends StLink
{
    const SRC_FLODER = '/public';
    const GLOBAL_SRC = '/public/static/src';
    const ADDONS_SRC = '/public/styles';

    private function inDevEnv()
    {
        if (config('app_debug')) {
            return true;
        } else {
            return false;
        }
    }

    public function __construct($url = '', $isAddon = false)
    {
        if (is_bool($url)) {
            $isAddon = $url;
            $url = '';
        }
        $this->linkObj = new StLink($url);
        $tpHref = $this->getTpLink($this->linkObj->href);
        parent::__construct($tpHref);
        $this->isAddonUrl = $isAddon;
        $this->parsePath();
        if ($isAddon) {
            $this->parseAddonPath();
        }
    }

    private function getTpLink($url)
    {
        return preg_replace('/index.php(\?s=)*\//', '', $url);
    }

    private function parseAddonPath()
    {
        if (empty($this->path)) {
            $this->path = 'admin/base/index';
        }
        $path = $this->path;
        $floders = explode('/', $path);
        $this->tpAddonPath = $floders[3];
        list($addonModule, $addonController, $addonOperator) = explode('-', $this->tpAddonPath);
        if (stripos($addonOperator, '.')) {
            $this->suffix = substr(strstr($addonOperator, '.'), 1);
            $addonOperator = strstr($addonOperator, '.', true);
        }
        $this->addonModule = $addonModule;
        $this->addonController = $addonController;
        $this->addonOperator = $addonOperator;
    }

    private function parsePath()
    {
        $path = $this->path;
        $floders = explode('/', $path);
        $this->tpModule = $floders[1];
        $this->tpController = $floders[2];
        $this->suffix = 'html';
        $this->tpAction = $floders[3];
        if (stripos($this->tpAction, '.')) {
            $this->suffix = substr(strstr($this->tpAction, '.'), 1);
            $this->tpAction = strstr($this->tpAction, '.', true);
        }
    }

    private function isCodeSrc($srcPath)
    {
        preg_match_all('/\.(css|js)$/', $srcPath, $matches);
        if (!empty($matches) && !empty($matches[0])) {
            return true;
        } else {
            return false;
        }
    }

    public function loadSrc($srcPath, $isGlobalSrc = false)
    {
        $path = $this->protocol . $this->host;
        if (empty($isGlobalSrc)) {
            $path .= self::SRC_FLODER . '/' . $this->tpModule . '/' . $this->tpController . '/' . $this->tpAction . '/' . $srcPath;
        } elseif ($isGlobalSrc == 'addon') {
            $path .= self::ADDONS_SRC . '/' . $srcPath;
        } else {
            $path .= self::GLOBAL_SRC . '/' . $srcPath;
        }
        if (stripos($path, self::SIBLING_FLODER_MARK) || stripos($path, self::PARENT_FLODER_MARK)) {
            $path = $this->getTruePath($path);
        }
        if ($this->inDevEnv() && $this->isCodeSrc($srcPath)) {
            $path .= '?t='.time();
        }
        return $path . getVersions();
    }

    public function loadAddonSrc($srcName, $style = '')
    {
        if ($srcName == 'js' || $srcName == 'css') {
            $srcName = strtolower($this->addonOperator) . '.' . $srcName;
        }
        $srcPath = $style . '/addons/' . strtolower($this->addonModule) . '/' . strtolower($this->addonController) . '/' . strtolower($this->addonOperator) . '/' . $srcName;
        return $this->loadSrc($srcPath, 'addon');
    }
}