import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import placesRouter from './routes/places';
import './database/schema'; // Initialize database

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/places', placesRouter);

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Server is running on http://localhost:${PORT}`);
  console.log(`📊 API: http://localhost:${PORT}/api/places`);
});

export default app;
