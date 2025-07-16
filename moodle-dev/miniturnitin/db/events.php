<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_assign\event\submission_created',
        'callback' => '\plagiarism_miniturnitin\observer::submission_created',
    ],
    [
        'eventname' => '\mod_assign\event\submission_updated',
        'callback' => '\plagiarism_miniturnitin\observer::submission_created',
    ],
];