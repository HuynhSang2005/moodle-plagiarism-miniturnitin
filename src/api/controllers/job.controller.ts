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

// const { jobId } = req.params;

//   try {
//     // Tìm job trong CSDL bằng jobId
//     const query = db.query(`SELECT * FROM jobs WHERE id = ?`);
//     const job = query.get(jobId);

//     if (!job) {
//       return res.status(404).json({ message: 'Job not found' });
//     }

//     // Trả về thông tin của job nếu tìm thấy
//     return res.status(200).json(job);

//   } catch (error) {
//     console.error('Failed to get job status:', error);
//     return res.status(500).json({ message: 'Internal Server Error' });
//   }
// };

export const getJobStatusHandler = async (req: Request, res: Response) => {
  const { jobId } = req.params;

  // Thêm bước kiểm tra này
  if (!jobId) {
    return res.status(400).json({ message: 'Job ID is required' });
  }

  try {
    // Tìm job trong CSDL bằng jobId
    const query = db.query(`SELECT * FROM jobs WHERE id = ?`);
    // Sử dụng query.get để lấy job theo ID
    const job = query.get(jobId);

    if (!job) {
      return res.status(404).json({ message: 'Job not found' });
    }

    // Trả về thông tin của job nếu tìm thấy
    return res.status(200).json(job);

  } catch (error) {
    console.error('Failed to get job status:', error);
    return res.status(500).json({ message: 'Internal Server Error' });
  }
};