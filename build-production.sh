#!/bin/bash

echo "🏗️  Building Production Version..."
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Step 1: Install dependencies
echo -e "${BLUE}📦 Installing dependencies...${NC}"
cd backend && npm install --production=false
cd ../frontend && npm install --production=false
cd ..

# Step 2: Build frontend
echo -e "${BLUE}🎨 Building frontend...${NC}"
cd frontend
npm run build
cd ..

# Step 3: Build backend
echo -e "${BLUE}⚙️  Building backend...${NC}"
cd backend
npm run build
cd ..

# Step 4: Create production directory
echo -e "${BLUE}📁 Creating production directory...${NC}"
rm -rf dist
mkdir -p dist

# Step 5: Copy files
echo -e "${BLUE}📋 Copying files...${NC}"
cp -r backend/dist dist/backend
cp -r backend/node_modules dist/node_modules
cp -r frontend/dist dist/frontend
cp backend/package.json dist/
cp backend/.env.example dist/.env

# Step 6: Create data directory
mkdir -p dist/data

# Step 7: Create start script
cat > dist/start.sh << 'EOF'
#!/bin/bash
export NODE_ENV=production
export PORT=3000
export DATABASE_PATH=./data/scraper.db
cd backend && node dist/server.js
EOF

chmod +x dist/start.sh

# Create Windows start script
cat > dist/start.bat << 'EOF'
@echo off
set NODE_ENV=production
set PORT=3000
set DATABASE_PATH=./data/scraper.db
cd backend
node dist/server.js
EOF

echo ""
echo -e "${GREEN}✅ Build complete!${NC}"
echo ""
echo "📦 Production files are in: ./dist"
echo ""
echo "To run in production:"
echo "  1. Upload the 'dist' folder to your server"
echo "  2. Run: ./start.sh (Linux/Mac) or start.bat (Windows)"
echo "  3. Access: http://your-server:3000"
echo ""
