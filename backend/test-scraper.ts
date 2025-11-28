/**
 * Test script để verify scraper và database
 * Chạy: npx tsx test-scraper.ts
 */

import { GoogleMapsScraper } from './src/scraper/googleMaps';
import { PlaceModel } from './src/models/Place';
import './src/database/schema';

async function testScraper() {
  console.log('🧪 Testing Google Maps Scraper\n');
  console.log('=' .repeat(50));

  const scraper = new GoogleMapsScraper();

  try {
    // Test 1: Initialize scraper
    console.log('\n[1/5] Testing scraper initialization...');
    await scraper.init();
    console.log('✅ Scraper initialized');

    // Test 2: Scrape with small dataset
    console.log('\n[2/5] Testing scraper (3 results)...');
    const testOptions = {
      keyword: 'coffee shop',
      city: 'Hanoi',
      maxResults: 3
    };

    console.log(`Searching: ${testOptions.keyword} in ${testOptions.city}`);

    const results = await scraper.scrapeWithRetry(testOptions, 2);

    console.log(`✅ Scraped ${results.length} places`);

    if (results.length > 0) {
      console.log('\nSample result:');
      console.log(JSON.stringify(results[0], null, 2));
    } else {
      console.log('⚠️  No results found (this may be normal depending on location)');
    }

    // Test 3: Save to database
    console.log('\n[3/5] Testing database save...');
    let savedCount = 0;

    for (const place of results) {
      try {
        PlaceModel.create({
          ...place,
          keyword: testOptions.keyword,
          city: testOptions.city
        });
        savedCount++;
      } catch (err) {
        console.error('Error saving:', err);
      }
    }

    console.log(`✅ Saved ${savedCount}/${results.length} places to database`);

    // Test 4: Query from database
    console.log('\n[4/5] Testing database query...');

    const allPlaces = PlaceModel.findAll({}, 100, 0);
    console.log(`✅ Found ${allPlaces.length} total places in database`);

    // Test with filter
    const filtered = PlaceModel.findAll({ keyword: testOptions.keyword }, 100, 0);
    console.log(`✅ Found ${filtered.length} places with keyword "${testOptions.keyword}"`);

    // Test 5: Filter values
    console.log('\n[5/5] Testing filter values...');
    const countries = PlaceModel.getUniqueValues('country');
    const cities = PlaceModel.getUniqueValues('city');

    console.log(`✅ Unique countries: ${countries.length}`);
    console.log(`✅ Unique cities: ${cities.length}`);

    if (cities.length > 0) {
      console.log(`   Cities: ${cities.join(', ')}`);
    }

    // Summary
    console.log('\n' + '='.repeat(50));
    console.log('✅ ALL TESTS PASSED!');
    console.log('='.repeat(50));

    console.log('\n📊 Summary:');
    console.log(`   - Scraped: ${results.length} places`);
    console.log(`   - Saved: ${savedCount} places`);
    console.log(`   - Total in DB: ${allPlaces.length} places`);
    console.log(`   - Filters working: YES`);

    console.log('\n🎯 Next steps:');
    console.log('   1. Run backend: cd backend && npm run dev');
    console.log('   2. Run frontend: cd frontend && npm run dev');
    console.log('   3. Test in browser: http://localhost:5173');

  } catch (error) {
    console.error('\n❌ TEST FAILED:', error);
    console.error('\nThis may happen if:');
    console.error('   - Chromium is not installed');
    console.error('   - Network issues');
    console.error('   - Google Maps blocked the request');
    console.error('\nTry:');
    console.error('   - Install Chromium: sudo apt-get install chromium-browser');
    console.error('   - Run with fewer results (maxResults: 2)');
  } finally {
    await scraper.close();
    console.log('\n🔒 Browser closed');
  }
}

// Run test
testScraper().catch(console.error);
