<?php
namespace plagiarism_miniturnitin;

// Import các lớp thư viện sẽ sử dụng
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

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

        // 1. Lấy thông tin cơ bản
        $submissionid = $event->objectid;
        $submission = $DB->get_record('assign_submission', ['id' => $submissionid], '*', MUST_EXIST);
        if ($submission->status != 'submitted') {
            return;
        }

        try {
            // 2. Lấy file đã nộp
            $fs = get_file_storage();
            $context = \context_module::instance($event->contextinstanceid);
            $files = $fs->get_area_files($context->id, 'mod_assign', 'submission_files', $submissionid, 'timemodified DESC', false);

            if (empty($files)) { return; }

            $file = reset($files);
            if ($file->is_directory()) { return; }

            // 3. Trích xuất nội dung text (PHẦN NÂNG CẤP)
            $filecontent = self::extract_text_from_file($file);

            if (empty($filecontent)) {
                return; // Bỏ qua nếu không thể trích xuất nội dung
            }
            
            // 4. Lấy cài đặt từ admin
            $api_url = get_config('plagiarism_miniturnitin', 'api_url');
            $secret_key = get_config('plagiarism_miniturnitin', 'api_secret_key');
            if (empty($api_url) || empty($secret_key)) { return; }

            // 5. Gọi API bằng cURL (Không thay đổi)
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
            
            // 6. Xử lý phản hồi (Không thay đổi)
            if ($httpcode == 202 && $response) {
                $responsedata = json_decode($response);
                if (isset($responsedata->jobId)) {
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
            error_log('MiniTurnitin Error: ' . $e->getMessage());
        }
    }

    /**
     * Trích xuất text từ Moodle file record, hỗ trợ txt, pdf, docx.
     *
     * @param \stored_file $file
     * @return string
     */
    private static function extract_text_from_file(\stored_file $file): string {
        $mimetype = $file->get_mimetype();
        $content = '';

        // Tạo một file tạm để các thư viện có thể đọc
        $tempfilepath = make_request_directory() . '/' . $file->get_filename();
        $file->copy_content_to($tempfilepath);

        try {
            switch ($mimetype) {
                case 'application/pdf':
                    $parser = new PdfParser();
                    $pdf = $parser->parseFile($tempfilepath);
                    $content = $pdf->getText();
                    break;

                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // .docx
                    $phpWord = WordIOFactory::load($tempfilepath);
                    $text = '';
                    $sections = $phpWord->getSections();
                    foreach ($sections as $section) {
                        $elements = $section->getElements();
                        foreach ($elements as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . ' ';
                            }
                        }
                    }
                    $content = $text;
                    break;
                
                // Giữ lại cách đọc file text đơn giản
                case 'text/plain':
                    $content = $file->get_content();
                    break;

                // TODO: Thêm các loại file khác nếu cần (ví dụ: .doc, .rtf)
            }
        } catch (\Exception $e) {
            // Ghi lại lỗi nếu thư viện không đọc được file
             error_log('MiniTurnitin File Extraction Error: ' . $e->getMessage());
             $content = ''; // Trả về chuỗi rỗng nếu có lỗi
        } finally {
            // Luôn xóa file tạm sau khi xử lý xong
            if (file_exists($tempfilepath)) {
                unlink($tempfilepath);
            }
        }

        return trim($content);
    }
}