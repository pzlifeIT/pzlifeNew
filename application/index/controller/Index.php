<?php

namespace app\index\controller;

use app\index\MyController;
use Env;
use Config;
use think\Db;
use \upload\Imageupload;

class Index extends MyController {
    protected $beforeActionList = [
//        'first',//所有方法的前置操作
//        'second' => ['except' => 'hello'],//除去hello其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    public function index() {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    /**
     * @api              {post} / 列表
     * @apiDescription   hello
     * @apiGroup         index
     * @apiName          hello
     * @apiParam {String} name name
     * @apiSampleRequest /index/hello
     * @author zyr
     */
    public function hello() {
//        print_r(Config::get('database.'));
//        $a = Db::name('user_relation')->all();
//        print_r($a);
        die;
    }

    /**
     * redis案例
     */
    public function redisTest() {
        $this->redis->set('key', 'test');
        echo $this->redis->get('key');
//        $this->redis->rPush('key11111', 'aaa');
//        echo $this->redis->rPop('key11111');
        die;
    }

    /**
     * 上传案例
     * @throws \Exception
     */
    public function uploadTest() {
        $file = $this->request->file('img');
//        print_r(\Reflection::export(new \ReflectionClass($file)));die;
        $fileInfo = $file->getInfo();
        $upload   = new Imageupload();
        $filename = $upload->getNewName($fileInfo['name']);
        $upload->uploadFile($fileInfo['tmp_name'], $filename);
//        $upload->deleteImage('head_01.jpg');
        die;
    }
}
