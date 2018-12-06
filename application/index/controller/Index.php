<?php

namespace app\index\controller;

use app\index\MyController;
use Env;

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
     * @apiSampleRequest /hello
     * @route('hello/:name/[:sign]/[:timestamp]')
     * @author zyr
     */
    public function hello($name = 'ThinkPHP5') {
        $params = $this->request->param();
        print_r($params);
        die;
//        $this->app->user->test();die;
//        print_r(Config::get('cache.redis'));die;
        return 'hello,' . $name;
    }
}
