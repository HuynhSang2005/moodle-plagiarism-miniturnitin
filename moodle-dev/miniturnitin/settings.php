<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN) {
    // Tạo một trang cài đặt mới cho plugin
    $settings = new admin_settingpage('plagiarism_miniturnitin', get_string('pluginname', 'plagiarism_miniturnitin'));

    // Thêm trường nhập URL của API Server
    $settings->add(new admin_setting_configtext(
        'plagiarism_miniturnitin/api_url',
        'API URL',
        'The base URL of the MiniTurnitin API server.',
        '',
        PARAM_URL
    ));

    // Thêm trường nhập Secret Key (dạng password)
    $settings->add(new admin_setting_configpasswordunmask(
        'plagiarism_miniturnitin/api_secret_key',
        'API Secret Key',
        'The secret key to authenticate with the API server.',
        ''
    ));

    $ADMIN->add('plagiarism', $settings);
}