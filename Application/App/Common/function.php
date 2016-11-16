<?php

function addLogs($msg)
{
    $data["content"] = $msg;
    $data["ctime"] = time();
    M("sys_log_me")->add($data);
}
