<?php
namespace plagiarism_miniturnitin;

defined('MOODLE_INTERNAL') || die();

class observer {
    /**
     * Lắng nghe sự kiện khi một bài nộp được tạo hoặc cập nhật.
     *
     * @param \mod_assign\event\submission_created|\mod_assign\event\submission_updated $event
     * @return void
     */
    
    public static function submission_created($event) {
        global $DB;

        // 1. Lấy thông tin cơ bản từ sự kiện
        $submissionid = $event->objectid;
        $userid = $event->relateduserid;

        // Lấy đối tượng submission để kiểm tra trạng thái
        $submission = $DB->get_record('assign_submission', ['id' => $submissionid], '*', MUST_EXIST);
        if ($submission->status != 'submitted') {
            // Chỉ xử lý khi bài đã ở trạng thái "submitted"
            return;
        }

        try {
            // 2. Lấy file đã nộp
            $fs = get_file_storage();
            $context = \context_module::instance($event->contextinstanceid);
            $files = $fs->get_area_files($context->id, 'mod_assign', 'submission_files', $submissionid, 'timemodified DESC', false);

            if (empty($files)) {
                // Không có file nào để xử lý
                return;
            }
            
            // Lấy file đầu tiên trong danh sách
            $file = reset($files);
            if ($file->is_directory()) {
                return; // Bỏ qua nếu là thư mục
            }

            // 3. Trích xuất nội dung text
            // LƯU Ý: get_content() chỉ hoạt động tốt với file .txt.
            // Để đọc .docx, .pdf, bạn cần tích hợp thư viện bên ngoài.
            $filecontent = $file->get_content();

            if (empty($filecontent)) {
                return; // Bỏ qua nếu nội dung rỗng
            }
            
            // 4. Lấy cài đặt từ admin
            $api_url = get_config('plagiarism_miniturnitin', 'api_url');
            $secret_key = get_config('plagiarism_miniturnitin', 'api_secret_key');

            if (empty($api_url) || empty($secret_key)) {
                // Thiếu cấu hình, không thể tiếp tục
                return;
            }

            // 5. Chuẩn bị và gọi API bằng cURL
            $endpoint = $api_url . '/api/v1/jobs';
            $payload = json_encode(['text' => $filecontent]);
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $secret_key
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // 6. Xử lý phản hồi và lưu vào CSDL
            if ($httpcode == 202 && $response) {
                $responsedata = json_decode($response);
                if (isset($responsedata->jobId)) {
                    // Xóa bản ghi cũ nếu có để tránh lỗi unique key
                    $DB->delete_records('plagiarism_miniturnitin_files', ['submissionid' => $submissionid]);

                    $record = new \stdClass();
                    $record->submissionid = $submissionid;
                    $record->jobid = $responsedata->jobId;
                    $record->status = 'queued';
                    $record->timecreated = time();
                    $record->timemodified = time();
                    $DB->insert_record('plagiarism_miniturnitin_files', $record);
                }
            }

        } catch (\Exception $e) {
            // Ghi lại lỗi nếu có
            error_log('MiniTurnitin Error: ' . $e->getMessage());
        }
    }
}