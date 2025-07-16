import { findBestMatch } from 'string-similarity';
import db from './db.service';

/**
 * Tách một đoạn văn bản thành mảng các câu.
 * Đây là một phương pháp đơn giản, có thể cải tiến sau này.
 * @param text - Đoạn văn bản đầu vào.
 * @returns Mảng các câu.
 */
const splitIntoSentences = (text: string): string[] => {
  // Tách câu dựa trên dấu chấm, hỏi, than và xuống dòng.
  // Lọc bỏ các câu rỗng.
  return text.split(/[.?!;\n]+/).filter(sentence => sentence.trim().length > 0);
};

/**
 * Xử lý một job kiểm tra đạo văn.
 * @param jobId - ID của job cần xử lý.
 * @param text - Nội dung văn bản của job.
 */
export const processPlagiarismCheck = async (jobId: string, text: string) => {
  console.log(`[Job ${jobId}] Starting plagiarism check...`);

  // Cập nhật trạng thái job thành 'processing'
  db.query(`UPDATE jobs SET status = 'processing' WHERE id = ?`).run(jobId);

  // TODO: Implement logic so sánh và tính điểm

  // Giả lập thời gian xử lý
  setTimeout(() => {
    const fakeScore = Math.floor(Math.random() * 100);
    console.log(`[Job ${jobId}] Processing complete. Score: ${fakeScore}`);

    // Cập nhật trạng thái job thành 'completed'
    db.query(`UPDATE jobs SET status = 'completed', score = ? WHERE id = ?`)
      .run(fakeScore, jobId);
  }, 5000); // Giả lập 5 giây xử lý
};