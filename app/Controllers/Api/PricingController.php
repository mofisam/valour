<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\{ClothingType, Service};

class PricingController extends Controller
{
    public function index(): void
    {
        $this->json(['services' => Service::active(), 'clothing_types' => ClothingType::active()]);
    }
}
