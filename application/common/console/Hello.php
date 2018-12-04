<?php

namespace app\common\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Hello extends Command {
    protected function configure() {
        $this->setName('hello')
            ->addArgument('name', Argument::OPTIONAL, "your name")
//            ->addArgument('sign', Argument::OPTIONAL, "your sign")
//            ->addOption('city', null, Option::VALUE_REQUIRED, 'name name')
            ->setDescription('Say Hello');
    }

    protected function execute(Input $input, Output $output) {
        $name = trim($input->getArgument('name'));
//        $sign = trim($input->getArgument('sign'));
//        $name = $name ?: 'thinkphp';
//        if ($input->hasOption('city')) {
//            $city = PHP_EOL . 'From ' . $input->getOption('city');
//        } else {
//            $city = '';
//        }
        $params        = ['name' => $name];
        $requestString = '';
        foreach ($params as $k => $v) {
            if (!is_array($v)) {
                $requestString .= $k . $v;
            }
        }
        $paramHash = hash_hmac('sha1', $requestString, 'pzlife');
        $output->writeln($paramHash);
    }
}