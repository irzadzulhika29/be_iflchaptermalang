<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Tripay\Main;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $tripay;

    public function __construct()
    {
        // $this->tripay = new Main(
        //     'DEV-0YVPfSLL5z8FOQbHouoHyDLm88X3ckoQpw6jF5M1',
        //     'DWAxT-LbIk0-A0BRy-IC4g2-NZqoj',
        //     'T35653',
        //     'sandbox' // fill for sandbox mode, leave blank if in production mode
        // );
        
        $this->tripay = new Main(
            '1xgq0s1aspxPPybMNmZDdc8CmpwX4stz5e6nR1D5',
            'ytOLh-VujpZ-LqMcl-8yCDE-CMJmX',
            'T35629',
            'live' // fill for sandbox mode, leave blank if in production mode
        );
    }
}
