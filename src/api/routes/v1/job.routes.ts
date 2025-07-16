import { Router } from 'express';
import { authMiddleware } from '../../middlewares/auth.middleware';
import { validate } from '../../middlewares/validate.middleware';
import { submitJobSchema } from '../../validators/job.validator';
import { submitJobHandler } from '../../controllers/job.controller';

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

export default router;