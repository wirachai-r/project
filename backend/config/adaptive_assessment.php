<?php

return [
    'minimum_clear_answers' => (int) env('ADAPTIVE_ASSESSMENT_MINIMUM_CLEAR_ANSWERS', 5),
    'candidate_limit' => (int) env('ADAPTIVE_ASSESSMENT_CANDIDATE_LIMIT', 5),
];
