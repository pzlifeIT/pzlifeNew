<?php

namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
//use think\console\input\Option;
use think\console\Output;
use Env;

class Pzlife extends Command {
    protected function configure() {
        $this->setName('console')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->addArgument('params', Argument::OPTIONAL, "your params")
//            ->addOption('city', null, Option::VALUE_REQUIRED, 'name name')
            ->setDescription('Say Hello');
    }

    protected function execute(Input $input, Output $output) {
        $commond = trim($input->getFirstArgument());
        $name    = trim($input->getArgument('name'));
        $params  = trim($input->getArgument('params'));
//        if ($input->hasOption('city')) {
//            $city = PHP_EOL . 'From ' . $input->getOption('city');
//        } else {
//            $city = '';
//        }
//        $output->writeln(self::class);die;
        $className = 'app\console\com\\' . ucfirst($commond);
        $params    = explode('}{', rtrim(ltrim($params, '{'), '}'));
        call_user_func_array([new $className(), $name], $params);
    }
}