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
    global $DB, $OUTPUT;

    $submissionid = $file_record->itemid;
    $our_record = $DB->get_record('plagiarism_miniturnitin_files', ['submissionid' => $submissionid]);

    if (!$our_record) {
        return '';
    }
    
    switch ($our_record->status) {
        case 'queued':
        case 'processing':
            $spinner = new \core\output\spinner();
            return $spinner->get_html() . ' Pending...';

        case 'error':
            $error_icon = $OUTPUT->pix_icon('t/delete', 'Error in processing');
            return $OUTPUT->render($error_icon);
            
        case 'completed':
            // SỬA ĐỔI TẠI ĐÂY
            $score = (int)$our_record->score;
            $color = '#28a745'; // Xanh lá
            if ($score > 25) {
                $color = '#ffc107'; // Vàng
            }
            if ($score > 60) {
                $color = '#dc3545'; // Đỏ
            }
            
            // Tạo link đến trang báo cáo chi tiết
            $report_link = new \moodle_url('/plagiarism/miniturnitin/report.php', ['id' => $our_record->id]);
            
            $style = "font-weight: bold; color: {$color}; padding: 2px 6px; border-radius: 4px; background-color: #f8f9fa;";
            
            // Tạo thẻ span chứa điểm số
            $score_html = html_writer::tag('span', "{$score}%", ['style' => $style]);
            
            // Trả về một thẻ <a> bao quanh điểm số
            return html_writer::link($report_link, $score_html, ['title' => 'View similarity report']);
            
        default:
            return '';
    }
}