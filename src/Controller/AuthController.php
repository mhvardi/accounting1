<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            header('Location: /');
            return;
        }
        View::render('auth/login', ['user'=>null,'error'=>null]);
    }

    public function login(): void
    {
        $identifier = $_POST['identifier'] ?? '';
        $password   = $_POST['password'] ?? '';
        if (Auth::attempt($identifier, $password)) {
            header('Location: /');
            return;
        }
        View::render('auth/login', ['user'=>null,'error'=>'ورود ناموفق بود.']);
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
    }
}
