import { findBestMatch } from 'string-similarity';
import db from './db.service';

/**
 * Tách một đoạn văn bản thành mảng các câu.
 */
const splitIntoSentences = (text: string): string[] => {
  return text.split(/[.?!;\n]+/).filter(sentence => sentence.trim().length > 0);
};

/**
 * Xử lý một job kiểm tra đạo văn.
 */
export const processPlagiarismCheck = (jobId: string, text: string) => {
  console.log(`[Job ${jobId}] Starting plagiarism check...`);
  
  // Cập nhật trạng thái job thành 'processing'
  db.query(`UPDATE jobs SET status = 'processing' WHERE id = ?`).run(jobId);

  try {
    // 1. Lấy tất cả các bài đã nộp TRƯỚC ĐÓ để làm nguồn so sánh
    const otherSubmissions = db.query(
        `SELECT content FROM submissions WHERE content != ?`
      ).all(text) as { content: string }[];

    // Nếu không có bài nào khác để so sánh, coi như 0% trùng lặp
    if (otherSubmissions.length === 0) {
      db.query(`UPDATE jobs SET status = 'completed', score = 0 WHERE id = ?`)
        .run(jobId);
      console.log(`[Job ${jobId}] No other submissions to compare against. Score: 0`);
      return;
    }

    // 2. Gộp tất cả các văn bản cũ thành một chuỗi lớn và tách thành câu
    const sourceSentences = otherSubmissions
      .map(sub => sub.content)
      .flatMap(content => splitIntoSentences(content));

    // 3. Tách văn bản mới thành các câu
    const newSentences = splitIntoSentences(text);

    if (newSentences.length === 0) {
      db.query(`UPDATE jobs SET status = 'completed', score = 0 WHERE id = ?`)
        .run(jobId);
      console.log(`[Job ${jobId}] Submission is empty. Score: 0`);
      return;
    }

    // 4. Tìm các câu trùng lặp
    let matchedSentencesCount = 0;
    const SIMILARITY_THRESHOLD = 0.8; // Ngưỡng tương đồng 80%

    for (const sentence of newSentences) {
      const { bestMatch } = findBestMatch(sentence, sourceSentences);
      if (bestMatch && bestMatch.rating >= SIMILARITY_THRESHOLD) {
        matchedSentencesCount++;
      }
    }

    // 5. Tính điểm cuối cùng
    const finalScore = Math.round((matchedSentencesCount / newSentences.length) * 100);

    // 6. Cập nhật kết quả vào database
    db.query(`UPDATE jobs SET status = 'completed', score = ? WHERE id = ?`)
      .run(finalScore, jobId);
    
    console.log(`[Job ${jobId}] Processing complete. Score: ${finalScore}`);
    
  } catch (error) {
    console.error(`[Job ${jobId}] Error during processing:`, error);
    db.query(`UPDATE jobs SET status = 'error' WHERE id = ?`).run(jobId);
  }
};