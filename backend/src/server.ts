import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import path from 'path';
import placesRouter from './routes/places';
import './database/schema'; // Initialize database

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;
const isProduction = process.env.NODE_ENV === 'production';

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// API Routes
app.use('/api/places', placesRouter);

// Health check
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    env: process.env.NODE_ENV
  });
});

// Serve static files in production
if (isProduction) {
  const frontendPath = path.join(__dirname, '../../frontend/dist');
  app.use(express.static(frontendPath));

  app.get('*', (req, res) => {
    res.sendFile(path.join(frontendPath, 'index.html'));
  });
}

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Server is running on http://localhost:${PORT}`);
  console.log(`📊 API: http://localhost:${PORT}/api/places`);
  console.log(`🌍 Environment: ${process.env.NODE_ENV || 'development'}`);
  if (isProduction) {
    console.log(`📱 Frontend: http://localhost:${PORT}`);
  }
});

export default app;
