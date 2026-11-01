<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Session, Validator};
use App\Models\{ActivityLog, Role, User};

class UserController extends Controller
{
    public function index(): void
    {
        $this->view('users/index', ['title' => 'Staff accounts', 'users' => User::listAll()]);
    }

    public function create(): void
    {
        $this->view('users/form', ['title' => 'New staff account', 'user' => null, 'roles' => Role::options()]);
    }

    public function store(): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'name'                  => 'required|max_length:255',
            'email'                 => 'required|email',
            'role_id'               => 'required|integer',
            'password'              => 'required|min_length:8',
            'password_confirmation' => 'required|same:password',
        ], ['password_confirmation' => 'Password confirmation']);
        if (!isset($errors['email']) && User::emailTaken($in['email'])) {
            $errors['email'] = 'That email is already in use.';
        }
        if ($errors) {
            Session::flashErrors($errors, $in);
            back('/users/create');
        }
        $id = User::insert([
            'name'          => $in['name'],
            'email'         => strtolower($in['email']),
            'password_hash' => password_hash($in['password'], PASSWORD_DEFAULT),
            'role_id'       => (int) $in['role_id'],
            'is_active'     => 1,
        ]);
        ActivityLog::log('user.created', 'user', $id, $in['name'] . ' (' . strtolower($in['email']) . ', ' . (array_column(Role::options(), 'label', 'id')[(int) $in['role_id']] ?? 'role') . ')');
        $this->flash('success', 'Account created.');
        $this->redirect('/users');
    }

    public function edit(string $id): void
    {
        $user = User::findWithRole((int) $id) ?? abort(404);
        $this->view('users/form', ['title' => 'Edit account', 'user' => $user, 'roles' => Role::options()]);
    }

    public function update(string $id): void
    {
        $user = User::findWithRole((int) $id) ?? abort(404);
        $in = $this->request->all();
        $rules = ['name' => 'required|max_length:255', 'email' => 'required|email', 'role_id' => 'required|integer'];
        if (($in['password'] ?? '') !== '') {
            $rules['password'] = 'min_length:8';
            $rules['password_confirmation'] = 'required|same:password';
        }
        $errors = Validator::validate($in, $rules, ['password_confirmation' => 'Password confirmation']);
        if (!isset($errors['email']) && User::emailTaken($in['email'], (int) $id)) {
            $errors['email'] = 'That email is already in use.';
        }

        $active = isset($in['is_active']) ? 1 : 0;
        $roleId = (int) ($in['role_id'] ?? 0);
        $roleName = array_column(Role::options(), 'name', 'id')[$roleId] ?? null;
        if ($roleName === null) {
            $errors['role_id'] = 'Choose a valid role.';
        }

        // Never lock the business out: keep at least one active admin.
        $wasAdmin = $user['role'] === 'admin' && (int) $user['is_active'] === 1;
        $staysAdmin = $roleName === 'admin' && $active === 1;
        if ($wasAdmin && !$staysAdmin && User::activeAdminCount() <= 1) {
            $errors['role_id'] = 'This is the only active administrator. Make someone else an admin first.';
        }

        if ($errors) {
            Session::flashErrors($errors, $in);
            back("/users/$id/edit");
        }

        $data = ['name' => $in['name'], 'email' => strtolower($in['email']), 'role_id' => $roleId, 'is_active' => $active];
        if (($in['password'] ?? '') !== '') {
            $data['password_hash'] = password_hash($in['password'], PASSWORD_DEFAULT);
        }
        User::update((int) $id, $data);
        ActivityLog::log('user.updated', 'user', (int) $id, $data['name'] . ' (' . $data['email'] . ', ' . $roleName . ', ' . ($active ? 'active' : 'deactivated') . (isset($data['password_hash']) ? ', password reset' : '') . ')');
        $this->flash('success', 'Account updated.');
        $this->redirect('/users');
    }
}
