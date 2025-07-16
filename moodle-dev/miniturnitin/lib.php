<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Hàm này được Moodle gọi để lấy HTML hiển thị bên cạnh mỗi bài nộp.
 * Tên hàm phải theo quy ước: plagiarism_{pluginname}_get_links
 *
 * @param stdClass $file_record Đối tượng chứa thông tin về file submission.
 * @return string Đoạn mã HTML để hiển thị.
 */
function plagiarism_miniturnitin_get_links($file_record) {
    global $DB;

    // Lấy submission ID từ đối tượng file_record
    // itemid trong context này chính là submission id.
    $submissionid = $file_record->itemid;

    // Tìm trạng thái của file này trong bảng CSDL của chúng ta
    $our_record = $DB->get_record('plagiarism_miniturnitin_files', ['submissionid' => $submissionid]);

    if (!$our_record) {
        // Không có thông tin gì về file này
        return '';
    }
    
    // Dựa vào trạng thái để trả về HTML tương ứng
    switch ($our_record->status) {
        case 'queued':
        case 'processing':
            // Hiển thị icon loading và text "Pending"
            $spinner = new \core\output\spinner();
            return $spinner->get_html() . ' Pending...';

        case 'error':
            // Hiển thị icon lỗi
            $error_icon = new \core\output\pix_icon('t/delete', 'Error in processing');
            return $error_icon->render();
            
        case 'completed':
            // Hiển thị điểm số với màu sắc
            $score = (int)$our_record->score;
            $color = '#28a745'; // Xanh lá (Mặc định)
            if ($score > 25) {
                $color = '#ffc107'; // Vàng
            }
            if ($score > 60) {
                $color = '#dc3545'; // Đỏ
            }
            
            // TODO: Tạo link đến một trang báo cáo chi tiết
            $report_link = new \moodle_url('/plagiarism/miniturnitin/report.php', ['id' => $our_record->id]);
            
            $style = "font-weight: bold; color: {$color}; padding: 2px 6px; border-radius: 4px; background-color: #f8f9fa;";
            
            // Tạm thời chỉ hiển thị điểm số, chưa có link
            return "<span style='{$style}'>{$score}%</span>";
            
        default:
            return '';
    }
}