<?php
/**
 * Date: 2019/4/3
 * Time: 10:25
 */

require_once '../vendor/autoload.php';
//echo \Configure\ConfigureNode::getInstance('.env')->get('username');
//
//echo '<br>';

echo \Configure\ConfigureNode::getInstance('.env')->pull('http://192.168.26.28/core/ConfigSynchronize/getConfig','3f4f160cacdc79b05e83bd82321ad304','http://192.168.26.28/core/ConfigSynchronize/isFinish');