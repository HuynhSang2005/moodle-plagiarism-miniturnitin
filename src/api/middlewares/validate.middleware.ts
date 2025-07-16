import { type Request, type Response, type NextFunction } from 'express';
import { z, ZodError } from 'zod';

export const validate = (schema: z.Schema) => 
  (req: Request, res: Response, next: NextFunction) => {
    try {
      // Parse và validate request dựa trên schema được cung cấp
      schema.parse({
        body: req.body,
        query: req.query,
        params: req.params,
      });
      next();
    } catch (error) {
      if (error instanceof ZodError) {
        return res.status(400).json({
          message: 'Validation failed',
          errors: error.issues,
        });
      }
      // Chuyển các lỗi khác đi
      next(error);
    }
  };