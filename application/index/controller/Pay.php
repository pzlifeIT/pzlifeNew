<?php

namespace app\index\controller;

use app\index\MyController;
use Env;

class Pay extends MyController
{
    protected $beforeActionList = [
        //        'first',//所有方法的前置操作
        //        'second' => ['except' => 'hello'],//除去hello其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    public function index(){
        echo 'index';
        exit;
    }

    /**
     * @api {post} /pay 支付
     * @apiDescription 支付模块
     * @apiGroup pay
     * @apiName payrt
     * @apiParam {String} [Token] yes
     * @apiParam {String} [paymement] yes 支付方式
     * @apiParam {String} [pay_cost] yes 支付金额
     * @apiSampleRequest /pay/pay
     */
    public function pay()
    {
        $paymement = $this->request->param['paymement'];
        
        // $test= $this->app->pay->test();
        $newtest = $this->app->commission->test();
        // $test = $PayService->test();
        echo '<pre>';
        // var_dump($test);
        var_dump($newtest);
        echo '</pre>';
        exit;

    }

    

}
