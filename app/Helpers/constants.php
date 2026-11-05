<?php
// Shared lists used by controllers and views.
// Job statuses are NOT listed here any more: the admin manages them under Services & prices > Process steps
// (see job_steps() in Helpers/functions.php). Only 'cancelled' is built in.

const PAYMENT_STATUSES  = ['unpaid', 'partial', 'paid'];
const PAYMENT_METHODS   = ['cash', 'transfer', 'pos', 'other'];

// Colours an admin can give a process step. Literal class names so Tailwind can see them.
const STEP_COLORS = [
    'amber'   => 'bg-amber-100 text-amber-900 dark:bg-amber-400/15 dark:text-amber-200',
    'orange'  => 'bg-orange-100 text-orange-900 dark:bg-orange-400/15 dark:text-orange-200',
    'sky'     => 'bg-sky-100 text-sky-900 dark:bg-sky-400/15 dark:text-sky-200',
    'indigo'  => 'bg-indigo-100 text-indigo-900 dark:bg-indigo-400/15 dark:text-indigo-200',
    'violet'  => 'bg-violet-100 text-violet-900 dark:bg-violet-400/15 dark:text-violet-200',
    'fuchsia' => 'bg-fuchsia-100 text-fuchsia-900 dark:bg-fuchsia-400/15 dark:text-fuchsia-200',
    'rose'    => 'bg-rose-100 text-rose-900 dark:bg-rose-400/15 dark:text-rose-200',
    'teal'    => 'bg-teal-100 text-teal-900 dark:bg-teal-400/15 dark:text-teal-200',
    'emerald' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-400/15 dark:text-emerald-200',
    'lime'    => 'bg-lime-100 text-lime-900 dark:bg-lime-400/15 dark:text-lime-200',
    'slate'   => 'bg-slate-200 text-slate-800 dark:bg-slate-400/15 dark:text-slate-200',
];
// Same colours for charts
const STEP_HEX = [
    'amber' => '#D9A441', 'orange' => '#E07B39', 'sky' => '#4A90C2', 'indigo' => '#6C7BD6', 'violet' => '#8E6CC9',
    'fuchsia' => '#B45FC9', 'rose' => '#D9607A', 'teal' => '#1F9AA6', 'emerald' => '#3FA06B', 'lime' => '#8DB63C', 'slate' => '#7A8B99',
];
