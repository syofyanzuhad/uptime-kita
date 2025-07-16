<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class TestFlashController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('dashboard')->with('flash', [
            'message' => 'This is a test flash message!',
            'type' => 'success',
        ]);
    }
}
