import { Router } from 'express';
import { authMiddleware } from '../../middlewares/auth.middleware';
import { validate } from '../../middlewares/validate.middleware';
import { submitJobSchema } from '../../validators/job.validator';
import { submitJobHandler, getJobStatusHandler  } from '../../controllers/job.controller';


const router = Router();

/**
 * @openapi
 * /api/v1/jobs:
 * post:
 * summary: Submit a new text document for similarity analysis
 * tags: [Jobs]
 * security:
 * - bearerAuth: []
 * requestBody:
 * required: true
 * content:
 * application/json:
 * schema:
 * type: object
 * properties:
 * text:
 * type: string
 * description: The full text content to be analyzed.
 * example: "Đây là một đoạn văn bản mẫu để kiểm tra."
 * responses:
 * 202:
 * description: Job accepted and queued for processing.
 * content:
 * application/json:
 * schema:
 * type: object
 * properties:
 * jobId:
 * type: string
 * format: uuid
 * 400:
 * description: Validation error.
 * 401:
 * description: Unauthorized.
 * 403:
 * description: Forbidden.
 */
router.post(
  '/',
  authMiddleware,
  validate(submitJobSchema),
  submitJobHandler
);

/** <-- THÊM ROUTE MỚI BẮT ĐẦU TỪ ĐÂY
 * @openapi
 * /api/v1/jobs/{jobId}:
 * get:
 * summary: Retrieve the status and result of a specific analysis job
 * tags: [Jobs]
 * security:
 * - bearerAuth: []
 * parameters:
 * - in: path
 * name: jobId
 * required: true
 * schema:
 * type: string
 * format: uuid
 * description: The ID of the job to retrieve.
 * responses:
 * 200:
 * description: Successfully retrieved job status.
 * content:
 * application/json:
 * schema:
 * type: object
 * properties:
 * id:
 * type: string
 * status:
 * type: string
 * enum: [queued, processing, completed, error]
 * score:
 * type: integer
 * result:
 * type: string
 * createdAt:
 * type: string
 * format: date-time
 * 404:
 * description: Job not found.
 */
router.get(
  '/:jobId',
  authMiddleware,
  getJobStatusHandler
);

export default router;