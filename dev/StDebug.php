<?php
namespace akaba\dev;

class StDebug
{
    private $debugTrace;
    public $testVar;
    private $varType;
    private $defaultStyles = [
        'extend/akaba/dev/StDebug.css',
    ];
    static $hasLoadedStyle = false;
    public function __construct($options = '')
    {
        $this->getOptions($options);
    }
    public function loadStyle()
    {
        if (self::$hasLoadedStyle) {
            return '';
        }
        $style = $this->options['styleUrl'];
        if (empty($style)) {
            $styleIndex = $this->options['style'];
            $style = $this->defaultStyles[$styleIndex];
        }
        $style = '<link rel="stylesheet" href="' . $style . '">';
        self::$hasLoadedStyle = true;
        return $style;
    }
    public function showDebugs($start = 0)
    {
        $tracesTree = $this->debugTrace;
        $wide = $this->options['traceLevel'];
        if (empty($wide)) {
            $traces = $tracesTree;
        } else {
            $traces = array_slice($tracesTree, $start, $wide);
        }
        $html = '';
        foreach ($traces as $trace) {
            $line = (string) $trace['line'];
            $html .= '
                <div class="trace-row">
                    <span class="trace-file">' . $trace['file'] . '</span>
                    <span class="trace-line">' . $line . '</span>
                </div>
            ';
        }
        return $html;
    }

    public function dump($var, $start = 1)
    {

        $styles = $this->loadStyle();
        $this->testVar = $var;
        $this->debugTrace = \debug_backtrace();
        $hasLoadStyle = self::$hasLoadedStyle;
        $debugs = $this->showDebugs($start);
        $varInfo = $this->debug($var);
        $html = $styles . '<div class="stdump">' . $debugs . $varInfo . '</div>';
        echo $html;
        if ($this->options['exit']) {
            exit;
        }
    }
    public function debug($var)
    {
        if ($this->options['simpleText']) {
            $html = $this->simpleDebug($var);
        } else {
            $html = $this->htmlizeDebug($var);
        }
        return $html;
    }
    public function simpleDebug($var)
    {
        ob_start();
        var_dump($var);
        $onCache = ob_get_contents();
        ob_clean();
        return "<pre>{$onCache}</pre>";
    }
    public function htmlizeDebug($var)
    {
        $type = getType($var);
        switch ($type) {
            case 'array':
                $size = sizeOf($var);
                $html = $this->debugArray($var);
                break;
            case 'boolean':
                $html = $this->debugBoolean($var);
                break;
            case 'object':
                $size = get_class($var);
                $html = $this->debugObject($var);
                break;
            default:
                $html = $this->debugString($var, $type);
                break;
        }
        if (isset($size)) {
            $sizeStr = (string) $size;            
            return '
                <div class="dump-var">
                    <div class="dump-var-base">
                        <span class="dump-var-type">'.$type.'</span>
                        <span class="dump-var-size">'.$sizeStr.'</span>
                    </div>
                    <div class="dump-var-content">'.$html.'</div>
                </div>
            ';
        } 
        return $html;
    }
    public function debugObject($var)
    {
        $reflectObj = new \ReflectionObject($var);
        $props = $reflectObj->getProperties();
        $constants = $reflectObj->getConstants();
        $constantsDIV = $this->getConstDIV($constants);
        $objPropsDIV = $this->getObjPropsDIV($var, $props);
        $methods = $reflectObj->getMethods();
        $methodsDIV = $this->getObjMethDIV($methods);
        return '<div class="var-obj">' . $constantsDIV . $objPropsDIV . $methodsDIV . '</div>';
    }
    private function getConstDIV($constants)
    {
        $html = '';
        if (is_array($constants)) {
            foreach ($constants as $key => $const) {
                $html .= '
                    <div class="const-prop">
                        <span class="prop-type">const</span>
                        <span class="const-key">' . $key . '</span>
                        <span class="const-value">' . $const . '</span>
                    </div>
                ';
            }
        }
        return $html;
    }
    public function arr2Span($arr)
    {
        $str = '';
        foreach ($arr as $key => $arr) {
            $str .= '<span class="func-param">' . $key . '=>' . $arr . ',</span>';
        }
        return $str;
    }
    private function getMethodParams($methodObj)
    {
        $params = $methodObj->getParameters();
        $paramsDescDIV = '';
        $funcName = $methodObj->name;
        if (is_array($params) && sizeOf($params) > 0) {
            foreach ($params as $paramObj) {
                $paramType = $paramObj->getType();
                if (empty($paramType)) {
                    $paramType = 'any';
                }
                $defaultValue = '';
                if ($paramObj->isDefaultValueAvailable()) {
                    $defaultValue = $paramObj->getDefaultValue();
                    $defaultValue = '\'\'';
                    $defaultValue = $this->setDefaultValue($defaultValue);
                }
                if (!empty($defaultValue)) {
                    $defaultValue = '<span class="param-default">' . $defaultValue . '</span>';
                }
                $paramsDescDIV .= '
                        <span class="param-describe">
                            <span class="param-type">' . $paramType . '</span>
                            <span class="param-name">' . $paramObj->name . '</span>
                            ' . $defaultValue . '
                        </span>
                    ';
            }
        }
        return '<span class="func-params">' . $paramsDescDIV . '</span>';
    }
    private function setDefaultValue($defaultValue)
    {
        $defaultArray = [
            'array' => '[]',
            'string' => '\'\'',
            'boolean' => 'false',
        ];
        $type = getType($defaultValue);
        if (empty($defaultValue)) {
            $key = $type;
            if (!in_array($type, $defaultArray)) {
                $key = 'string';
            }
            return $defaultArray[$key];
        }
        if ($type == 'boolean') {
            return 'true';
        }
        return $defaultValue;
    }
    private function getObjMethDIV($methods)
    {
        $objDIV = '';
        if (is_array($methods)) {
            foreach ($methods as $methodObj) {
                $methodName = $methodObj->name;
                $modifier = $this->getModifier($methodObj, 'method');
                $keyDiv = '
                        <em class="obj-prop-class">' . $methodObj->class . '</em>
                        <span class="obj-method-modifiers">' . $modifier['html'] . '</span>
                        <span class="type-method">function</span>
                        <span class="obj-method-name">' . $methodName . '</span>
                    ';
                $paramsDIV = $this->getMethodParams($methodObj);
                $objDIV .= '
                        <div class="obj-method">
                            <span class="obj-method-abstract">' . $keyDiv . '</span>
                            <span class="obj-method-params">' . $paramsDIV . '</span>
                        </div>
                    ';
            }
        }
        return $objDIV;
    }
    private function getModifier($refProp, $type = 'prop')
    {
        $html = '';
        $arr = [];
        if ($refProp->isPrivate()) {
            $html .= '<span class="modifier-private">private</span>';
            array_push($arr, 'private');
        }
        if ($refProp->isPublic()) {
            $html .= '<span class="modifier-public">public</span>';
            array_push($arr, 'public');
        }
        if ($refProp->isProtected()) {
            $html .= '<span class="modifier-protected">protected</span>';
            array_push($arr, 'protected');
        }
        if ($refProp->isStatic()) {
            $html .= '<span class="modifier-static">static</span>';
            array_push($arr, 'static');
        }
        if ($type === 'method') {
            if ($refProp->isAbstract()) {
                $html .= '<span class="modifier-abstract">abstract</span>';
                array_push($arr, 'abstract');
            }
            if ($refProp->isFinal()) {
                $html .= '<span class="modifier-final">final</span>';
                array_push($arr, 'final');
            }
        }
        if (empty($arr)) {
            $html = '<span class="modifier-public">public</span>';
            array_push($arr, 'public');
        }
        $modifier = [
            'html' => $html,
            'list' => $arr,
        ];
        return $modifier;
    }
    private function getObjPropsDIV($obj, $props)
    {
        $objDIV = '';
        if (is_array($props) && sizeOf($props) > 0) {
            foreach ($props as $propObj) {
                $prop = $propObj->name;
                $modifier = $this->getModifier($propObj);
                $keyDiv = '
                        <em class="obj-prop-class">' . $propObj->class . '</em>
                        <span class="obj-prop-modifiers">' . $modifier['html'] . '</span>
                        <span class="obj-prop-name">' . $prop . '</span>
                    ';
                if (in_array('public', $modifier['list'])) {
                    $val = $obj->$prop;
                    $valDIV = $this->debug($val);
                } else {
                    $valDIV = '';
                }
                $objDIV .= '
                    <div class="obj-prop">
                    <div class="obj-prop-key">' . $keyDiv . '</div>
                    <div class="obj-prop-val">' . $valDIV . '</div>
                    </div>
                ';
            }
        }
        return $objDIV;
    }
    public function debugBoolean($var)
    {
        if (empty($var)) {
            $var = 'false';
        } else {
            $var = 'true';
        }
        $html = '
            <div class="debug-item">
                <span class="item-type">boolean</span>
                <span class="item-value">' . $var . '</span>
            </div>
        ';
        return $html;
    }
    public function debugArray($arr)
    {
        $itemsHTML = '';
        if (!empty($arr)) {
            foreach ($arr as $key => $item) {
                $keyHtml = '<div class="arr-key">' . $key . '</div>';
                $varHTML = '<div class="arr-value">' . $this->debug($item) . '</div>';
                $itemsHTML .= '<div class="arr-item">' . $keyHtml . $varHTML . '</div>';
            }
        }
        return '
            <div class="arr-block">
                ' . $itemsHTML . '
            </div>
        ';
    }
    public function debugString($var, $type = '')
    {
        if (empty($type)) {
            $type = getType($var);
        }
        $len = strLen($var);
        if ($type == 'string') {
            if (empty($var)) {
                $var = '\'\'';
            }
        } else {
            $var = (string) $var;
        }
        $html = '
            <div class="debug-item">
                <span class="item-type">' . $type . '</span>
                <span class="item-len">' . $len . '</span>
                <span class="item-value">' . $var . '</span>
            </div>
        ';
        return $html;
    }
    private function getOptions($opts = '')
    {
        $defaultOptions = [
            'style' => 0,
            'styleUrl' => '',
            'exit' => false,
            'traceDebug' => true,
            'traceVar' => false,
            'traceLevel' => 1,
            'simpleText' => false,
        ];
        if (empty($opts) && $opts != 0) {
            $opts = true;
        }
        if (\is_numeric($opts)) {
            $defaultOptions['traceLevel'] = $opts;
        }
        if (\is_bool($opts)) {
            $defaultOptions['simpleText'] = $opts;
        }
        if (is_array($opts)) {
            foreach ($opts as $key => $opt) {
                if (isset($defaultOptions[$key])) {
                    $defaultOptions[$key] = $opt;
                }
            }
        }
        $this->options = $defaultOptions;
    }
}
