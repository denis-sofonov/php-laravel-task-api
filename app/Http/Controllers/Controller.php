<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Даёт всем контроллерам метод $this->authorize() для проверки политик.
    use AuthorizesRequests;
}
