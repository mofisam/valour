<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Session};
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', ['title' => 'Sign in'], 'login');
    }

    public function login(): void
    {
        $email = strtolower((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');

        if ($email === '' || $password === '') {
            Session::flashErrors(['login' => 'Enter your email and password.'], ['email' => $email]);
            $this->redirect('/login');
        }

        $error = Auth::attempt($email, $password);
        if ($error) {
            Session::flashErrors(['login' => $error], ['email' => $email]);
            $this->redirect('/login');
        }

        ActivityLog::log('login', 'user', \App\Core\Auth::id(), 'Signed in as ' . $email);
        $intended = Session::get('intended', '/');
        Session::forget('intended');
        $this->redirect($intended);
    }

    public function logout(): void
    {
        ActivityLog::log('logout', 'user', Auth::id(), 'Signed out: ' . (Auth::user()['email'] ?? ''));
        Auth::logout();
        Session::start();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/login');
    }

    /** Keep-alive from the browser while the person is typing/using the page. The middleware stamps the activity. */
    public function ping(): void
    {
        $this->json(['ok' => true, 'idle_limit' => Session::idleLimit()]);
    }

    /** Where the browser's idle timer sends people. Only signs out if the server agrees the session really is idle. */
    public function expired(): void
    {
        if (Auth::check() && Session::idleSeconds() < Session::idleLimit()) {
            $this->redirect('/');
        }
        if (Auth::check()) {
            Auth::logout();
            Session::start();
        }
        Session::flash('info', 'You were signed out after ' . (int) config('app.idle_minutes', 10) . ' minutes without activity. Please sign in again.');
        $this->redirect('/login');
    }
}
