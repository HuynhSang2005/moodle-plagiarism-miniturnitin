import express, { type Request, type Response } from 'express';
import { initializeDB } from './services/db.service';
import v1Router from './api/routes/v1';

const app = express();
const PORT = process.env.PORT || 3000;

app.use('/api/v1', v1Router); 

// Initialize database
initializeDB();

// Middleware parse JSON body
app.use(express.json());

// Endpoint health check của API
app.get('/health', (req: Request, res: Response) => {
  res.status(200).json({ status: 'ok', message: 'API is running' });
});

app.listen(PORT, () => {
  console.log(`🚀 API server is listening on http://localhost:${PORT}`);
});