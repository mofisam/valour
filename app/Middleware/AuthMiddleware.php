<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Auth, Request, Session};

class AuthMiddleware
{
    /** Background polling must not keep a session alive, otherwise nobody would ever time out. */
    private const NO_TOUCH = ['/api/reminders/due'];

    public function handle(Request $request): void
    {
        if (Auth::check()) {
            // Signed in: enforce the idle limit
            if (Session::idleSeconds() >= Session::idleLimit()) {
                Auth::logout();
                Session::start();
                Session::flash('info', 'You were signed out after ' . (int) config('app.idle_minutes', 10) . ' minutes without activity. Please sign in again.');
                $this->deny($request, true);
            }
            if (!in_array($request->path(), self::NO_TOUCH, true)) {
                Session::touch();
            }
            return;
        }
        $this->deny($request, false);
    }

    private function deny(Request $request, bool $expired): never
    {
        if ($request->isAjax()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => $expired ? 'expired' : 'Unauthenticated']);
            exit;
        }
        if (!$expired) {
            Session::put('intended', $request->path());
        }
        redirect('/login');
    }
}
