<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, Validator};
use App\Models\{ActivityLog, Expense};
use App\Core\Model;

/** Expense categories. "Direct cost" categories feed gross profit on the Reports page. */
class ExpenseCategoryController extends Controller
{
    public function index(): void
    {
        $cats = Model::all(
            'SELECT c.*, (SELECT COUNT(*) FROM expenses e WHERE e.category_id = c.id) AS uses FROM expense_categories c ORDER BY c.is_direct_cost DESC, c.name'
        );
        $this->view('expenses/categories', ['title' => 'Expense categories', 'categories' => $cats]);
    }

    public function save(?string $id = null): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, ['name' => 'required|max_length:100']);
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/expense-categories');
        }
        $direct = isset($in['is_direct_cost']) ? 1 : 0;
        try {
            if ($id === null) {
                Model::run('INSERT INTO expense_categories (name, is_direct_cost) VALUES (?, ?)', [$in['name'], $direct]);
                ActivityLog::log('expense_category.created', 'expense_category', Model::lastId(), $in['name']);
            } else {
                Model::run('UPDATE expense_categories SET name = ?, is_direct_cost = ? WHERE id = ?', [$in['name'], $direct, (int) $id]);
                ActivityLog::log('expense_category.updated', 'expense_category', (int) $id, $in['name']);
            }
            $this->flash('success', 'Category saved.');
        } catch (\mysqli_sql_exception $e) {
            $this->flash('error', $e->getCode() === 1062 ? 'A category with that name already exists.' : 'Could not save the category.');
        }
        $this->redirect('/expense-categories');
    }

    public function destroy(string $id): void
    {
        $uses = (int) Model::scalar('SELECT COUNT(*) FROM expenses WHERE category_id = ?', [(int) $id]);
        if ($uses > 0) {
            $this->flash('error', "This category is used by $uses expense(s). Rename it instead of deleting it.");
        } else {
            Model::run('DELETE FROM expense_categories WHERE id = ?', [(int) $id]);
            ActivityLog::log('expense_category.deleted', 'expense_category', (int) $id);
            $this->flash('success', 'Category deleted.');
        }
        $this->redirect('/expense-categories');
    }
}
