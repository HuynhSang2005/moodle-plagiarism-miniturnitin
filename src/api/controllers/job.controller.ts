import { type Request, type Response } from 'express';
import { randomUUID } from 'crypto';
import db from '../../services/db.service';

export const submitJobHandler = async (req: Request, res: Response) => {
  const { text } = req.body;
  const jobId = randomUUID();

  try {
    // Thêm job mới vào database với trạng thái "queued"
    const jobQuery = db.query(
      `INSERT INTO jobs (id, status) VALUES (?, 'queued')`
    );
    jobQuery.run(jobId);

    // Thêm nội dung văn bản vào bảng submissions
    const submissionQuery = db.query(
      `INSERT INTO submissions (content) VALUES (?)`
    );
    submissionQuery.run(text);

    // Phản hồi ngay lập tức với 202 (Accepted) và jobId
    // cho client biết yêu cầu đã được chấp nhận và đang được xử lý
    return res.status(202).json({ 
      jobId: jobId,
      message: 'Job has been accepted and is queued for processing.'
    });

  } catch (error) {
    console.error('Failed to create job:', error);
    return res.status(500).json({ message: 'Internal Server Error' });
  }
};