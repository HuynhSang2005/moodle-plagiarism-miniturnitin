<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\plagiarism_miniturnitin\task\check_status_task',
        'blocking' => 0, // 0 nghĩa là không block các tác vụ khác
        'minute' => '*/5', // Chạy mỗi 5 phút
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];