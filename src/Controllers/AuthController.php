<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        include __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (Auth::attempt($email, $password)) {
            $this->redirect('/');
        }
        $error = 'Invalid credentials';
        include __DIR__ . '/../Views/auth/login.php';
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/?route=login');
    }
}
