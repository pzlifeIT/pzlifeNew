<?php

namespace app\index\controller;

use app\index\MyController;
use Env;
use Config;
use think\Db;
use \upload\Imageupload;

use \third\PHPTree;

class Index extends MyController {
    protected $beforeActionList = [
//        'first',//所有方法的前置操作
//        'second' => ['except' => 'hello'],//除去hello其他方法都进行second前置操作
//        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    public function index() {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function register() {
//        echo sha1('1');die;
//        echo strlen('o83f0wKdXM2KZF7YVKnD9q86rELY');die;
        $res = $this->app->user->register(1, '1');
        print_r($res);
        die;
    }

    public function hello() {
//echo date('ymdHis');die;
//        print_r(   str_split(substr(uniqid(), 7, 13), 1)     );die;

//        print_r(Config::get('app.'));die;
//        ini_set('memory_limit', '512M');
//        $sql = "select * from pre_member as pm inner join pre_member_relationship as pmr on pm.uid=pmr.uid";
//        $res = Db::connect(Config::get('pzapidev.'))->query($sql);

//        $user = new Users();
//        $user->save([
//            'sex'=>2,
//            'last_time'=>time(),
//            'create_time'=>date('Y-m-d H:i:s'),
//        ]);
//        die;

//        $res = Users::where('users.id','in',[1,2])->field('user_type,nick_name')->withJoin('userRelation')->select();
//        $res->userRelation;
//        echo Db::getlastSql();
//        die;
//        print_r($res->toArray());
//        die;


        $sql = "select uid,pid from pz_user_relation";
        $res = Db::query($sql);

        $phptree = new PHPTree($res);
        $r       = $phptree->listTree();

        print_r($r);
        die;
    }

    /**
     * redis案例
     */
    public function redisTest() {
//        $this->redis->set('key', 'test');
//        echo $this->redis->get('key');
//        $this->redis->rPush('key11111', 'aaa');
//        echo $this->redis->rPop('key11111');


        $this->redis->zAdd('key', 1, 'val1');
        $this->redis->zAdd('key', 3, 'val0');
        $this->redis->zAdd('key', 2, 'val5');
        $this->redis->zIncrBy('key', 2, 'val1');
        print_r($this->redis->zRange('key', 0, -1, true)); // array(val0, val1, val5)
        $this->redis->delete('key');
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
