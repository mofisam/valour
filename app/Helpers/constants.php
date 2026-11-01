<?php
// Shared lists used by controllers and views.

// Every status a job can have, in the order a job normally moves through them.
const JOB_FLOW = ['pending', 'in_use', 'washing', 'dry_cleaning', 'ironing', 'ready', 'delivered'];
const JOB_STATUSES = ['pending', 'in_use', 'washing', 'dry_cleaning', 'ironing', 'ready', 'delivered', 'cancelled'];
const JOB_LABELS = [
    'pending' => 'Pending', 'in_use' => 'In use', 'washing' => 'Washing', 'dry_cleaning' => 'Dry cleaning', 'ironing' => 'Ironing',
    'ready' => 'Ready', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];
// Steps the admin can switch on or off per service. 'pending' and 'delivered' are always part of a job.
const OPTIONAL_STEPS = ['in_use', 'washing', 'dry_cleaning', 'ironing', 'ready'];

const PAYMENT_STATUSES  = ['unpaid', 'partial', 'paid'];
const PAYMENT_METHODS   = ['cash', 'transfer', 'pos', 'other'];
