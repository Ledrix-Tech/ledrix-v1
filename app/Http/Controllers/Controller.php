<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected $admin;

    public function __construct()
    {
        $this->admin = Auth::guard('admin')->user();
    }
}
