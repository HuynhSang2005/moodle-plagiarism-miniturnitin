<?php

require_once('../../config.php');
global $DB, $PAGE, $OUTPUT;

// 1. Lấy ID của bản ghi từ URL
$reportid = required_param('id', PARAM_INT); // Lấy id của record trong bảng plagiarism_miniturnitin_files

// 2. Lấy dữ liệu báo cáo từ CSDL của Moodle
$report_data = $DB->get_record('plagiarism_miniturnitin_files', ['id' => $reportid], '*', MUST_EXIST);
$submission = $DB->get_record('assign_submission', ['id' => $report_data->submissionid], '*', MUST_EXIST);
$assignment = $DB->get_record('assign', ['id' => $submission->assignment], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $assignment->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);

// 3. Kiểm tra quyền truy cập
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/assign:view', $context); // Đảm bảo người dùng có quyền xem bài nộp

// 4. Thiết lập trang Moodle
$PAGE->set_url('/plagiarism/miniturnitin/report.php', ['id' => $reportid]);
$PAGE->set_title(get_string('pluginname', 'plagiarism_miniturnitin'));
$PAGE->set_heading("Similarity Report");
$PAGE->set_context($context);

echo $OUTPUT->header();

// 5. Gọi API để lấy báo cáo chi tiết
// Giả định API của bạn có một endpoint như: /api/v1/jobs/{jobid}/report
$api_url = get_config('plagiarism_miniturnitin', 'api_url');
$secret_key = get_config('plagiarism_miniturnitin', 'api_secret_key');

// Nếu chưa có API thì hiển thị thông báo
if (empty($api_url) || empty($secret_key)) {
    echo $OUTPUT->notification('The plagiarism API is not configured. Please contact the administrator.');
    echo $OUTPUT->footer();
    exit;
}

$endpoint = $api_url . '/api/v1/jobs/' . $report_data->jobid . '/report'; // Cần endpoint này ở server
$headers = ['Authorization: Bearer ' . $secret_key];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Hiển thị kết quả
echo $OUTPUT->box_start();

if ($httpcode == 200 && $response) {
    $detailed_report = json_decode($response);

    // Hiển thị điểm tổng quan
    echo "<h2>Overall Similarity: " . (int)$report_data->score . "%</h2>";
    echo "<hr>";

    // Hiển thị danh sách các nguồn trùng lặp
    // Giả định API trả về một mảng 'sources'
    if (!empty($detailed_report->sources)) {
        echo "<h3>Matched Sources:</h3>";
        $table = new html_table();
        $table->head = ['Source', 'Similarity'];
        $table->data = [];
        foreach ($detailed_report->sources as $source) {
            $table->data[] = [$source->url, $source->percentage . '%'];
        }
        echo html_writer::table($table);
    } else {
        echo "<p>No detailed sources found.</p>";
    }
    
} else {
    echo $OUTPUT->notification('Could not retrieve the detailed report from the server. Please try again later.');
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();