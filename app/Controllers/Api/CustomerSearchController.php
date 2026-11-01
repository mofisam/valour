<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Customer;

class CustomerSearchController extends Controller
{
    public function index(): void
    {
        $q = (string) $this->request->input('q', '');
        $rows = mb_strlen($q) < 2 ? [] : Customer::search($q);
        if (!is_admin()) {
            $rows = array_map(fn($r) => ['id' => $r['id'], 'full_name' => $r['full_name'], 'phone_number' => $r['phone_number'], 'outstanding' => 0], $rows);
        }
        $this->json($rows);
    }
}
