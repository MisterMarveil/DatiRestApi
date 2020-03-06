<?php

namespace App\Utils;

class Logger 
{
    public function writeLog($txt){        
        //Save string to log, use FILE_APPEND to append.
        file_put_contents(__DIR__."/../../var/log/log_".date("j.n.Y").'.log', $txt, FILE_APPEND);
       
    }
}
