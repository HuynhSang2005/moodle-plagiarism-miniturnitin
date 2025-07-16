import { Router } from 'express';
import jobRoutes from './job.routes';

const router = Router();

router.use('/jobs', jobRoutes);

export default router;