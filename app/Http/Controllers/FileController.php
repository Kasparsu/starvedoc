<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(){
        return view('filetree');
    }
    public function data(){
        $data[]= ['text' => 'prefabs', 'id' => 'prefabs', 'children' => $this->getPrefabs('scripts/prefabs')];
        $data[]= ['text' => 'components', 'id' => 'components', 'children' => $this->getComponents('scripts/components')];
        $data[]= ['text' => 'widgets', 'id' => 'widgets', 'children' => $this->getWidgets('scripts/widgets')];
        return $data;
    }
    public function  test(){
        //$data = $this->getWidgets('scripts/widgets');
        var_dump(strpos('AnimButton:OnGainFocus', 'AnimButtonsda'));
    }
    private function getPrefabs($folder){

        $prefabFiles = Storage::files($folder);
        $prefabs = [];
        foreach($prefabFiles as $key=>$file) {
            $contents = Storage::get($file);
            $functions = $this->getDataFromStrToStr($contents, "local function ", '(');
            $components = $this->getDataFromStrToStr($contents, 'inst:AddComponent("', '")');
            $obj['id'] = $obj['text'] = basename($file, '.lua');
            $obj['children'] = [['text' => 'functions', 'id' => $obj['id']. '-functions', 'children' => $functions],
                                ['text' => 'components', 'id' => $obj['id']. '-components', 'children' => $components]];
            $prefabs[] = $obj;
        }
        return $prefabs;
    }
    private function getComponents($folder){

        $componentFiles = Storage::files($folder);
        $components = [];
        foreach($componentFiles as $key=>$file) {
            $obj = [];
            $contents = Storage::get($file);
            $class = $this->getClassName($contents);
            $functions = $this->getDataFromStrToStr($contents, "function ", '(', $class);
            $obj['id'] = $obj['text'] = basename($file, '.lua');
            if($class != false) {
                $classFunctions = $this->getDataFromStrToStr($contents, "function " . $class . ":", '(');
                $obj['children'][] =
                    ['text' => 'class ' . $class, 'id' => $obj['id'] . '-class', 'children' => $classFunctions];
            }
            $obj['children'][] = ['text' => 'functions', 'id' => $obj['id']. '-functions', 'children' => $functions];
            $components[] = $obj;
        }
        return $components;
    }
    private function getWidgets($folder){

        $widgetFiles = Storage::files($folder);
        $widgets = [];
        foreach($widgetFiles as $key=>$file) {
            $obj = [];
            $contents = Storage::get($file);

            $class = $this->getClassName($contents);
            $functions = $this->getDataFromStrToStr($contents, "function ", '(', $class);
            $obj['id'] = $obj['text'] = basename($file, '.lua');
            if($class != false) {
                $classFunctions = $this->getDataFromStrToStr($contents, "function " . $class . ":", '(');
                $obj['children'][] =
                    ['text' => 'class ' . $class, 'id' => $obj['id'] . '-class', 'children' => $classFunctions];
            }
            $obj['children'][] = ['text' => 'functions', 'id' => $obj['id']. '-functions', 'children' => $functions];

            $widgets[] = $obj;
        }
        return $widgets;
    }
    private function getDataFromStrToStr($string, $str1, $str2, $class = "NOTCLASS") {
        $positions = [];
        $values = [];
        $lastPos = 0;
        while (($lastPos = strpos($string, $str1, $lastPos)) !== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen($str1);
        }
        foreach ($positions as $value) {
            $end = strpos($string, $str2, $value);
            if($end !== false){
                $func = substr($string, $value + strlen($str1), $end - ($value + strlen($str1)));
                $hasclass = strpos($func, $class);
                if($hasclass=== false) {
                    $values[] = $func;
                }
            }
        }
        return $values;
    }

    private function getClassName($string) {
       $canditates = $this->getDataFromStrToStr($string, 'local ', 'Class');
        if($canditates != []) {
            foreach ($canditates as $key => $canditate) {
                if ($canditate == false) {
                    unset($canditates[$key]);
                }
            }
            usort($canditates, function ($a, $b) {
                return strlen($a) - strlen($b);
            });
            $name = trim(str_replace('=', '', $canditates[0]));
            if(strpos($name, "\n") !== false){
                $name = str_replace("\n", '', strrchr($name, "\n"));
            }
            return $name;
        }
        return false;
    }
}
