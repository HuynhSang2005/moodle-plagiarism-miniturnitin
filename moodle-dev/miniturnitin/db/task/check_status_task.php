<?php
namespace plagiarism_miniturnitin\task;

defined('MOODLE_INTERNAL') || die();

class check_status_task extends \core\task\scheduled_task {

    /**
     * Lấy tên có thể đọc được của tác vụ.
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'plagiarism_miniturnitin') . ': Check job status';
    }

    /**
     * Hàm chính sẽ được cron của Moodle thực thi.
     */
    public function execute() {
        global $DB;
        mtrace('Running MiniTurnitin status check...');

        // Lấy các job đang chờ xử lý từ CSDL
        $pending_jobs = $DB->get_records_sql(
            "SELECT * FROM {plagiarism_miniturnitin_files} WHERE status = ? OR status = ?",
            ['queued', 'processing']
        );

        if (empty($pending_jobs)) {
            mtrace('No pending jobs found.');
            return;
        }
        
        // Lấy cấu hình API
        $api_url = get_config('plagiarism_miniturnitin', 'api_url');
        $secret_key = get_config('plagiarism_miniturnitin', 'api_secret_key');

        if (empty($api_url) || empty($secret_key)) {
            mtrace('API configuration is missing.');
            return;
        }

        foreach ($pending_jobs as $job) {
            mtrace("Checking job ID: {$job->jobid}");

            $endpoint = $api_url . '/api/v1/jobs/' . $job->jobid;
            $headers = ['Authorization: Bearer ' . $secret_key];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode == 200 && $response) {
                $responsedata = json_decode($response);
                if (isset($responsedata->status) && ($responsedata->status == 'completed' || $responsedata->status == 'error')) {
                    // Cập nhật bản ghi trong CSDL
                    $job->status = $responsedata->status;
                    $job->score = $responsedata->score ?? null;
                    $job->timemodified = time();
                    $DB->update_record('plagiarism_miniturnitin_files', $job);
                    mtrace("Updated job ID {$job->jobid} to status '{$job->status}' with score {$job->score}");
                }
            } else {
                mtrace("Failed to get status for job ID {$job->jobid}. HTTP code: {$httpcode}");
            }
        }
        mtrace('MiniTurnitin status check finished.');
    }
}