import { type Request, type Response, type NextFunction } from 'express';

export const authMiddleware = (req: Request, res: Response, next: NextFunction) => {
  const authHeader = req.headers.authorization;

  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ message: 'Unauthorized: Missing or invalid token' });
  }

  const token = authHeader.split(' ')[1];

  if (token !== process.env.API_SECRET_KEY) {
    return res.status(403).json({ message: 'Forbidden: Invalid token' });
  }

  // Token hợp lệ, cho phép request đi tiếp
  next();
};