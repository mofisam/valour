<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, Session, Validator};
use App\Models\{ActivityLog, ClothingType, Service, ServicePrice};

/** Admin screen for services (Wash, Iron, per-kg…) and clothing types with default prices. */
class ServiceController extends Controller
{
    public function index(): void
    {
        $this->view('services/index', [
            'title'         => 'Services & prices',
            'services'      => Service::everything(),
            'clothingTypes' => ClothingType::everything(),
            'priceMap'      => ServicePrice::map(),
        ]);
    }

    public function saveService(?string $id = null): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'name'          => 'required|max_length:100',
            'pricing_model' => 'required|in:per_item,per_kg,per_hour',
            'rate'          => 'numeric|min:0',
        ]);
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/services');
        }
        $data = [
            'name'          => $in['name'],
            'pricing_model' => $in['pricing_model'],
            // One "rate" box on the form: it means ₦ per kg for weight services and ₦ per hour for machine-time services
            'price_per_kg'  => $in['pricing_model'] === 'per_kg' ? round((float) ($in['rate'] ?? 0), 2) : 0,
            'price_per_hour'=> $in['pricing_model'] === 'per_hour' ? round((float) ($in['rate'] ?? 0), 2) : 0,
            'workflow'      => Service::workflowFromSteps((array) ($in['steps'] ?? [])),
            'is_active'     => isset($in['is_active']) ? 1 : 0,
        ];
        try {
            if ($id === null) {
                $data['is_active'] = 1;
                $newId = Service::insert($data);
                ActivityLog::log('service.created', 'service', $newId, $data['name'] . ' (' . match ($data['pricing_model']) { 'per_kg' => 'per kg', 'per_hour' => 'per hour', default => 'per item' } . '; steps: ' . $data['workflow'] . ')');
            } else {
                Service::update((int) $id, $data);
                ActivityLog::log('service.updated', 'service', (int) $id, $data['name'] . ' (steps: ' . $data['workflow'] . ($data['pricing_model'] === 'per_kg' ? ', ' . money($data['price_per_kg']) . '/kg' : ($data['pricing_model'] === 'per_hour' ? ', ' . money($data['price_per_hour']) . '/hour' : '')) . ')');
            }
            $this->flash('success', 'Service saved.');
        } catch (\mysqli_sql_exception $e) {
            $this->flash('error', $e->getCode() === 1062 ? 'A service with that name already exists.' : 'Could not save the service.');
        }
        $this->redirect('/services');
    }

    public function saveClothingType(?string $id = null): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'name'           => 'required|max_length:100',
            'price_per_item' => 'required|numeric|min:0',
        ]);
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/services');
        }
        $data = [
            'name'           => strtolower($in['name']),
            'display_name'   => ($in['display_name'] ?? '') !== '' ? $in['display_name'] : null,
            'price_per_item' => round((float) $in['price_per_item'], 2),
            'is_active'      => isset($in['is_active']) ? 1 : 0,
        ];
        try {
            if ($id === null) {
                $data['is_active'] = 1;
                $newId = ClothingType::insert($data);
                ActivityLog::log('clothing_type.created', 'clothing_type', $newId, $data['name'] . ', fallback price ' . money($data['price_per_item']));
            } else {
                ClothingType::update((int) $id, $data);
                ActivityLog::log('clothing_type.updated', 'clothing_type', (int) $id, $data['name'] . ', fallback price ' . money($data['price_per_item']));
            }
            $this->flash('success', 'Clothing type saved.');
        } catch (\mysqli_sql_exception $e) {
            $this->flash('error', $e->getCode() === 1062 ? 'A clothing type with that name already exists.' : 'Could not save the clothing type.');
        }
        $this->redirect('/services');
    }

    /** Save the whole price grid (service x clothing type) in one go. Blank cell = no specific price. */
    public function savePrices(): void
    {
        $grid = (array) $this->request->input('prices', []);
        $changed = 0;
        foreach ($grid as $serviceId => $row) {
            foreach ((array) $row as $typeId => $value) {
                $value = trim((string) $value);
                if ($value !== '' && (!is_numeric($value) || (float) $value < 0)) {
                    continue; // ignore garbage instead of failing the whole grid
                }
                ServicePrice::set((int) $serviceId, (int) $typeId, $value === '' ? null : round((float) $value, 2));
                $changed++;
            }
        }
        ActivityLog::log('prices.updated', 'service', null, "Price list saved ($changed cells checked)");
        $this->flash('success', 'Price list saved.');
        $this->redirect('/services#prices');
    }
}
