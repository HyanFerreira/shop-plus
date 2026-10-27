<?php

return [
    'enabled' => (bool) env('ACADEMIC_AUTOMATION_ENABLED', false),
    'probabilities' => [
        'approved' => (int) env('ACADEMIC_APPROVED_PERCENT', 70),
        'declined' => (int) env('ACADEMIC_DECLINED_PERCENT', 20),
        'pending' => (int) env('ACADEMIC_PENDING_PERCENT', 10),
    ],
];
