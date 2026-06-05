<?php

// Moodle integration config: token aur function names .env se aate hain taake production mein secure rahen.
return [
    'base_url' => env('MOODLE_BASE_URL', 'http://moodle.test'),
    'rest_url' => env('MOODLE_REST_URL', 'http://moodle.test/webservice/rest/server.php'),
    'token' => env('MOODLE_WS_TOKEN', ''),
    'format' => 'json',
    'timeout' => (int) env('MOODLE_TIMEOUT', 15),
    'hmac' => [
        'enabled' => filter_var(env('MOODLE_HMAC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'secret' => env('MOODLE_HMAC_SECRET', ''),
        'timestamp_window' => (int) env('MOODLE_HMAC_TIMESTAMP_WINDOW', 300),
    ],
    'functions' => [
        // Custom plugin endpoints: user create, enrol, suspend aur progress report.
        'create_user' => env('MOODLE_FN_CREATE_USER', 'local_custom_webservice_create_user'),
        'enrol_user' => env('MOODLE_FN_ENROL_USER', 'local_custom_webservice_enrol_user'),
        'suspend_user' => env('MOODLE_FN_SUSPEND_USER', 'local_custom_webservice_suspend_user'),
        'user_progress' => env('MOODLE_FN_USER_PROGRESS', 'local_custom_webservice_get_course_users_progress'),
        // Built-in Moodle endpoints: catalog/category/user lookup ke liye.
        'courses' => env('MOODLE_FN_COURSES', 'core_course_get_courses'),
        'categories' => env('MOODLE_FN_CATEGORIES', 'core_course_get_categories'),
        'users_by_field' => env('MOODLE_FN_USERS_BY_FIELD', 'core_user_get_users_by_field'),
        'update_users' => env('MOODLE_FN_UPDATE_USERS', 'core_user_update_users'),
        'activity_completion' => env('MOODLE_FN_ACTIVITY_COMPLETION', 'core_completion_get_activities_completion_status'),
        'grades' => env('MOODLE_FN_GRADES', 'gradereport_user_get_grade_items'),
    ],
    'signed_functions' => [
        env('MOODLE_FN_CREATE_USER', 'local_custom_webservice_create_user'),
        env('MOODLE_FN_ENROL_USER', 'local_custom_webservice_enrol_user'),
        env('MOODLE_FN_SUSPEND_USER', 'local_custom_webservice_suspend_user'),
    ],
];
