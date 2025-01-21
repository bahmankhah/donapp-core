<?php

namespace App\Controllers;

use App\Facades\Vendor;

class TestController{

    public function test(){
        Vendor::donap()->giveAccess("0945974396", ["6778470a9f232b9fb8d32321"]);
        return true;
    }
}