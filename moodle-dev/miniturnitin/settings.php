<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('plagiarism_miniturnitin', get_string('pluginname', 'plagiarism_miniturnitin'));

    $settings->add(new admin_setting_configtext(
        'plagiarism_miniturnitin/api_url',
        get_string('apiurl', 'plagiarism_miniturnitin'),
        get_string('apiurl_desc', 'plagiarism_miniturnitin'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'plagiarism_miniturnitin/api_secret_key',
        get_string('apisecret', 'plagiarism_miniturnitin'),
        get_string('apisecret_desc', 'plagiarism_miniturnitin'),
        ''
    ));

    $ADMIN->add('plagiarismsettings', $settings); 
}
