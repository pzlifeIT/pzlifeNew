<?php

namespace app\console\com;

use app\console\Pzlife;
use think\Db;

class TemporaryScript extends Pzlife {

    /**
     * 修改数据库脚本
     *
     */
    public function ModifyDataScript(){
        /* 将大鲨鱼(13122511746)钻石购买关系挂在黄甍(13381867868)下面  2019/04/12 */
        $user = Db::query("SELECT * FROM pz_users WHERE mobile = 13122511746 AND delete_time=0 ");
        $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13381867868 AND delete_time=0 ");
        Db::table('pz_diamondvip_get')->where('uid', $user[0]['id'])->update(['share_uid'=>$up_user[0]['id']]);

        /* 画(18033698601)钻石购买关系挂在葛小薇(13280730253)下面  2019/04/12 */
        $user = Db::query("SELECT * FROM pz_users WHERE mobile = 18033698601 AND delete_time=0 ");
        $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13280730253 AND delete_time=0 ");
        Db::table('pz_diamondvip_get')->where('uid', $user[0]['id'])->update(['share_uid'=>$up_user[0]['id']]);

        /* 画(18033698601)层级关系挂在葛小薇(13280730253)下面  2019/04/12 */
        $user = Db::query("SELECT * FROM pz_users WHERE mobile = 18033698601 AND delete_time=0 ");
        $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13280730253 AND delete_time=0 ");
        Db::table('pz_user_relation')->where('uid', $user[0]['id'])->update(['relation'=>$up_user[0]['id'].','.$user[0]['id'],'pid' =>$up_user[0]['id'] ]);

        /* 开店关系修正
        应江总（江胜）要求开店邀请未接收到邀请者ID造成的数据出错
        王恒念(13914041717)店铺开通关系关系归属于张学军(13606221728)  2019/04/15 */
        Db::table('pz_shop_apply')->where('id', 3)->update(['refe_uid'=>15122,'refe_uname' =>'张学军','create_time' => '1555218828']);
        Db::table('pz_shop_apply')->where('id', 2)->update(['create_time' => '1555210837']);
        Db::table('pz_shop_apply')->where('id', 1)->update(['create_time' => '1555139928']);
        Db::table('pz_log_invest')->where('id', 75)->update(['uid'=>15122]);
        exit('ok!!');
    }
}
