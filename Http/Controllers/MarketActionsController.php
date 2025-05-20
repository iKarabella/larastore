<?php

namespace App\Modules\Larastore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Larastore\Http\Requests\Market\NotifyAboutAdmissionRequest;
use App\Modules\Larastore\Http\Requests\Market\SaveCartRequest;
use App\Models\SubsForAdmission;

class MarketActionsController extends Controller
{
    public function saveCart(SaveCartRequest $request):void
    {
        $request->session()->put('user_cart', $request->cart);
    }
    
    public function notifyAboutAdmission(NotifyAboutAdmissionRequest $request):void
    {
        SubsForAdmission::create($request->validated());
    }
}
