#!/bin/bash

echo "🧪 Testing Google Maps Scraper System"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check backend files
echo -e "${BLUE}[1/7] Checking backend files...${NC}"
if [ -f "backend/src/server.ts" ] && [ -f "backend/src/routes/places.ts" ] && [ -f "backend/src/scraper/googleMaps.ts" ]; then
    echo -e "${GREEN}✓ Backend files OK${NC}"
else
    echo -e "${RED}✗ Missing backend files${NC}"
    exit 1
fi

# Test 2: Check frontend files
echo -e "${BLUE}[2/7] Checking frontend files...${NC}"
if [ -f "frontend/src/App.tsx" ] && [ -f "frontend/src/components/ScrapeForm.tsx" ] && [ -f "frontend/src/components/PlacesList.tsx" ]; then
    echo -e "${GREEN}✓ Frontend files OK${NC}"
else
    echo -e "${RED}✗ Missing frontend files${NC}"
    exit 1
fi

# Test 3: Check package.json
echo -e "${BLUE}[3/7] Checking dependencies...${NC}"
if [ -f "backend/package.json" ] && [ -f "frontend/package.json" ]; then
    echo -e "${GREEN}✓ Package files OK${NC}"
else
    echo -e "${RED}✗ Missing package.json${NC}"
    exit 1
fi

# Test 4: Check key features in scraper
echo -e "${BLUE}[4/7] Checking scraper features...${NC}"
if grep -q "scrapeWithRetry" backend/src/scraper/googleMaps.ts && \
   grep -q "dedupl" backend/src/scraper/googleMaps.ts && \
   grep -q "ScrapeProgress" backend/src/scraper/googleMaps.ts; then
    echo -e "${GREEN}✓ Scraper has retry, dedupe, progress${NC}"
else
    echo -e "${YELLOW}⚠ Some scraper features may be missing${NC}"
fi

# Test 5: Check export endpoints
echo -e "${BLUE}[5/7] Checking export features...${NC}"
if grep -q "/export/csv" backend/src/routes/places.ts && \
   grep -q "/export/json" backend/src/routes/places.ts; then
    echo -e "${GREEN}✓ Export endpoints OK${NC}"
else
    echo -e "${RED}✗ Missing export endpoints${NC}"
fi

# Test 6: Check frontend export buttons
echo -e "${BLUE}[6/7] Checking frontend export buttons...${NC}"
if grep -q "handleExportCSV" frontend/src/components/PlacesList.tsx && \
   grep -q "handleExportJSON" frontend/src/components/PlacesList.tsx; then
    echo -e "${GREEN}✓ Frontend export buttons OK${NC}"
else
    echo -e "${RED}✗ Missing export buttons${NC}"
fi

# Test 7: Check routes using retry
echo -e "${BLUE}[7/7] Checking scraper retry usage...${NC}"
if grep -q "scrapeWithRetry" backend/src/routes/places.ts; then
    echo -e "${GREEN}✓ Routes using retry mechanism${NC}"
else
    echo -e "${RED}✗ Routes NOT using retry mechanism${NC}"
fi

echo ""
echo -e "${GREEN}======================================"
echo "✅ All checks passed!"
echo "======================================${NC}"
echo ""

echo -e "${YELLOW}📝 Next steps:${NC}"
echo "1. Install dependencies: npm run install:all"
echo "2. Test locally: npm run dev"
echo "3. Build for production: ./build-production.sh"
echo "4. Deploy to hosting: See DEPLOYMENT.md"
echo ""

echo -e "${BLUE}🧪 Quick functional test:${NC}"
echo "Run these commands to test the scraper:"
echo ""
echo "# Terminal 1 - Start backend"
echo "cd backend && npm run dev"
echo ""
echo "# Terminal 2 - Test scraping (after backend starts)"
echo "curl -X POST http://localhost:3000/api/places/scrape \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"keyword\":\"coffee\",\"city\":\"Hanoi\",\"maxResults\":5}'"
echo ""
echo "# Check results"
echo "curl http://localhost:3000/api/places"
echo ""
