<?php

return [
    'minimum_clear_answers' => (int) env('ADAPTIVE_ASSESSMENT_MINIMUM_CLEAR_ANSWERS', 5),
    'candidate_limit' => (int) env('ADAPTIVE_ASSESSMENT_CANDIDATE_LIMIT', 5),
    'soft_question_limit' => (int) env('ADAPTIVE_ASSESSMENT_SOFT_QUESTION_LIMIT', 10),
    'hard_question_limit' => (int) env('ADAPTIVE_ASSESSMENT_HARD_QUESTION_LIMIT', 15),
];
