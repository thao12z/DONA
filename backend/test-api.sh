#!/bin/bash

# Test API endpoints
# Usage: ./test-api.sh

echo "🧪 Testing API Endpoints"
echo "========================="
echo ""

BASE_URL="http://localhost:3000"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Test 1: Health check
echo -e "${BLUE}[1/6] Testing health endpoint...${NC}"
response=$(curl -s "${BASE_URL}/health")
if echo "$response" | grep -q "ok"; then
    echo -e "${GREEN}✅ Health check OK${NC}"
else
    echo -e "${RED}❌ Health check failed${NC}"
    echo "Make sure backend is running: cd backend && npm run dev"
    exit 1
fi

# Test 2: Get all places (empty)
echo -e "${BLUE}[2/6] Testing GET /api/places...${NC}"
response=$(curl -s "${BASE_URL}/api/places")
if echo "$response" | grep -q "success"; then
    count=$(echo "$response" | grep -o '"total":[0-9]*' | grep -o '[0-9]*')
    echo -e "${GREEN}✅ GET /api/places OK (${count} places)${NC}"
else
    echo -e "${RED}❌ GET /api/places failed${NC}"
fi

# Test 3: Get filter values
echo -e "${BLUE}[3/6] Testing GET /api/places/filters/country...${NC}"
response=$(curl -s "${BASE_URL}/api/places/filters/country")
if echo "$response" | grep -q "success"; then
    echo -e "${GREEN}✅ Filter endpoint OK${NC}"
else
    echo -e "${RED}❌ Filter endpoint failed${NC}"
fi

# Test 4: Scrape (this will take time!)
echo -e "${BLUE}[4/6] Testing POST /api/places/scrape...${NC}"
echo "   (This may take 30-60 seconds...)"
response=$(curl -s -X POST "${BASE_URL}/api/places/scrape" \
  -H "Content-Type: application/json" \
  -d '{
    "keyword": "coffee",
    "city": "Hanoi",
    "maxResults": 3
  }')

if echo "$response" | grep -q "success"; then
    scraped=$(echo "$response" | grep -o '"scraped":[0-9]*' | grep -o '[0-9]*')
    saved=$(echo "$response" | grep -o '"saved":[0-9]*' | grep -o '[0-9]*')
    echo -e "${GREEN}✅ Scraping OK (scraped: ${scraped}, saved: ${saved})${NC}"
else
    echo -e "${RED}❌ Scraping failed${NC}"
    echo "Response: $response"
fi

# Test 5: Check data was saved
echo -e "${BLUE}[5/6] Verifying data was saved...${NC}"
response=$(curl -s "${BASE_URL}/api/places")
count=$(echo "$response" | grep -o '"total":[0-9]*' | grep -o '[0-9]*')
if [ "$count" -gt 0 ]; then
    echo -e "${GREEN}✅ Data saved successfully (${count} places in DB)${NC}"
else
    echo -e "${RED}❌ No data in database${NC}"
fi

# Test 6: Test export
echo -e "${BLUE}[6/6] Testing export endpoints...${NC}"
csv_response=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/places/export/csv")
json_response=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/places/export/json")

if [ "$csv_response" = "200" ] && [ "$json_response" = "200" ]; then
    echo -e "${GREEN}✅ Export endpoints OK${NC}"
else
    echo -e "${RED}❌ Export endpoints failed (CSV: ${csv_response}, JSON: ${json_response})${NC}"
fi

echo ""
echo "========================="
echo -e "${GREEN}✅ API Tests Complete!${NC}"
echo "========================="
echo ""

echo "Try these URLs in browser:"
echo "  - Web UI: http://localhost:5173"
echo "  - API: ${BASE_URL}/api/places"
echo "  - Export CSV: ${BASE_URL}/api/places/export/csv"
echo "  - Export JSON: ${BASE_URL}/api/places/export/json"
echo ""
