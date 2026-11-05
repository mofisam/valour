<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, Model};
use App\Models\{ActivityLog, ProcessStep};

/** Admin: add, rename, recolour, reorder and remove the steps a job can move through. */
class ProcessStepController extends Controller
{
    public function index(): void
    {
        $steps = ProcessStep::ordered();
        $jobCounts = array_column(Model::all("SELECT status, COUNT(*) c FROM jobs WHERE deleted_at IS NULL GROUP BY status"), 'c', 'status');
        $services = Model::all('SELECT name, workflow FROM services ORDER BY name');
        foreach ($steps as &$s) {
            $s['jobs'] = (int) ($jobCounts[$s['step_key']] ?? 0);
            $s['services'] = array_values(array_map(fn($x) => $x['name'], array_filter($services, fn($x) => in_array($s['step_key'], array_map('trim', explode(',', (string) $x['workflow'])), true))));
        }
        unset($s);
        $this->view('process_steps/index', ['title' => 'Process steps', 'steps' => $steps]);
    }

    public function save(?string $id = null): void
    {
        $label = trim((string) $this->request->input('label', ''));
        $color = (string) $this->request->input('color', 'sky');
        if ($label === '' || mb_strlen($label) > 60) {
            $this->flash('error', 'Give the step a name (up to 60 characters).');
            $this->redirect('/process-steps');
        }
        if (!isset(STEP_COLORS[$color])) {
            $color = 'sky';
        }

        if ($id === null) {
            $after = ProcessStep::findByKey((string) $this->request->input('after', ''));
            $sort = $after ? (int) $after['sort_order'] + 5 : 55;
            $key = ProcessStep::makeKey($label);
            $newId = ProcessStep::insert(['step_key' => $key, 'label' => $label, 'color' => $color, 'sort_order' => $sort, 'is_locked' => 0]);
            ActivityLog::log('process_step.created', 'process_step', $newId, "$label (after " . ($after['label'] ?? 'the end') . ')');
            $this->flash('success', "Step “{$label}” added. Tick it on the services that use it.");
        } else {
            $step = ProcessStep::find((int) $id) ?? abort(404);
            $data = ['label' => $label, 'color' => $color];
            if (!$step['is_locked']) {
                $data['sort_order'] = max(1, min(89, (int) $this->request->input('sort_order', $step['sort_order'])));   // keeps Pending first and Delivered last
            }
            ProcessStep::update((int) $id, $data);
            ActivityLog::log('process_step.updated', 'process_step', (int) $id, $step['label'] . ($step['label'] !== $label ? " → $label" : '') . ' (' . $color . ')');
            $this->flash('success', 'Step saved.');
        }
        $this->redirect('/process-steps');
    }

    public function destroy(string $id): void
    {
        $step = ProcessStep::find((int) $id) ?? abort(404);
        if ($step['is_locked']) {
            $this->flash('error', "“{$step['label']}” is part of every job and can't be removed. You can rename it.");
        } elseif (($n = ProcessStep::jobsUsing($step['step_key'])) > 0) {
            $this->flash('error', "$n job" . ($n === 1 ? ' is' : 's are') . " still at “{$step['label']}”. Move them to another status first, then remove the step.");
        } else {
            ProcessStep::removeFromServices($step['step_key']);
            ProcessStep::delete((int) $id);
            ActivityLog::log('process_step.deleted', 'process_step', (int) $id, $step['label']);
            $this->flash('success', "Step “{$step['label']}” removed from all services.");
        }
        $this->redirect('/process-steps');
    }
}
