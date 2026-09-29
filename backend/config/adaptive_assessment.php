<?php

return [
    'max_questions' => (int) env('ADAPTIVE_ASSESSMENT_MAX_QUESTIONS', 12),
    'minimum_clear_answers' => (int) env('ADAPTIVE_ASSESSMENT_MINIMUM_CLEAR_ANSWERS', 5),
    'candidate_limit' => (int) env('ADAPTIVE_ASSESSMENT_CANDIDATE_LIMIT', 5),
];
