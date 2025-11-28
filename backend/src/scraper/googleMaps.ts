import puppeteer, { Browser, Page } from 'puppeteer';

export interface ScrapeOptions {
  keyword: string;
  country?: string;
  province?: string;
  city?: string;
  district?: string;
  ward?: string;
  maxResults?: number;
}

export interface ScrapedPlace {
  name: string;
  phone?: string;
  address?: string;
  latitude?: number;
  longitude?: number;
}

export interface ScrapeProgress {
  current: number;
  total: number;
  status: string;
}

export class GoogleMapsScraper {
  private browser: Browser | null = null;
  private progressCallback?: (progress: ScrapeProgress) => void;

  async init(): Promise<void> {
    if (this.browser) return;

    this.browser = await puppeteer.launch({
      headless: 'new',
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--disable-features=IsolateOrigins,site-per-process',
        '--blink-settings=imagesEnabled=false', // Disable images for speed
      ]
    });
  }

  setProgressCallback(callback: (progress: ScrapeProgress) => void): void {
    this.progressCallback = callback;
  }

  private updateProgress(current: number, total: number, status: string): void {
    if (this.progressCallback) {
      this.progressCallback({ current, total, status });
    }
  }

  async close(): Promise<void> {
    if (this.browser) {
      await this.browser.close();
      this.browser = null;
    }
  }

  private buildSearchQuery(options: ScrapeOptions): string {
    const locationParts = [
      options.ward,
      options.district,
      options.city,
      options.province,
      options.country
    ].filter(Boolean);

    const location = locationParts.join(', ');
    return location ? `${options.keyword} ${location}` : options.keyword;
  }

  async scrape(options: ScrapeOptions): Promise<ScrapedPlace[]> {
    if (!this.browser) {
      await this.init();
    }

    const page = await this.browser!.newPage();
    const results: ScrapedPlace[] = [];
    const maxResults = options.maxResults || 20;
    const seenNames = new Set<string>(); // Deduplicate

    try {
      // Set viewport and user agent
      await page.setViewport({ width: 1920, height: 1080 });
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

      const searchQuery = this.buildSearchQuery(options);
      const encodedQuery = encodeURIComponent(searchQuery);
      const url = `https://www.google.com/maps/search/${encodedQuery}`;

      this.updateProgress(0, maxResults, `Đang tìm kiếm: ${searchQuery}`);
      console.log(`🔍 Searching: ${searchQuery}`);

      await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

      // Wait for results to load
      try {
        await page.waitForSelector('div[role="feed"]', { timeout: 10000 });
      } catch (err) {
        console.log('No results found');
        return [];
      }

      this.updateProgress(0, maxResults, 'Đang tải danh sách địa điểm...');

      // Scroll to load more results
      await this.scrollResults(page, maxResults);

      // Get all place links
      const placeLinks = await page.evaluate(() => {
        const feed = document.querySelector('div[role="feed"]');
        if (!feed) return [];

        const links: string[] = [];
        const elements = feed.querySelectorAll('a[href*="/maps/place/"]');

        elements.forEach((el) => {
          const href = el.getAttribute('href');
          if (href && !links.includes(href)) {
            links.push(href);
          }
        });

        return links;
      });

      console.log(`📍 Found ${placeLinks.length} places`);
      const totalToScrape = Math.min(placeLinks.length, maxResults);

      // Extract details from each place
      for (let i = 0; i < totalToScrape; i++) {
        try {
          this.updateProgress(i + 1, totalToScrape, `Đang cào địa điểm ${i + 1}/${totalToScrape}`);

          const placeData = await this.extractPlaceFromUrl(page, placeLinks[i]);

          if (placeData && placeData.name && !seenNames.has(placeData.name)) {
            results.push(placeData);
            seenNames.add(placeData.name);
            console.log(`✓ ${i + 1}/${totalToScrape}: ${placeData.name}`);
          }

          // Small delay to avoid detection
          await page.waitForTimeout(500);
        } catch (err) {
          console.error(`❌ Error at ${i + 1}:`, err);
        }
      }

      this.updateProgress(totalToScrape, totalToScrape, `Hoàn thành! Đã cào ${results.length} địa điểm`);
      console.log(`✅ Scraped ${results.length} unique places`);
    } catch (error) {
      console.error('Scraping error:', error);
      throw error;
    } finally {
      await page.close();
    }

    return results;
  }

  private async scrollResults(page: Page, targetCount: number): Promise<void> {
    console.log(`🔄 Scrolling to load ${targetCount} results...`);
    let previousCount = 0;
    let stableCount = 0;
    const maxScrollAttempts = Math.max(30, Math.ceil(targetCount / 5)); // More aggressive scrolling

    for (let i = 0; i < maxScrollAttempts; i++) {
      // Scroll to bottom
      await page.evaluate(() => {
        const feed = document.querySelector('div[role="feed"]');
        if (feed) {
          feed.scrollTop = feed.scrollHeight;
        }
      });

      // Wait for new items to load
      await page.waitForTimeout(2000);

      // Check current count
      const currentCount = await page.evaluate(() => {
        const feed = document.querySelector('div[role="feed"]');
        if (!feed) return 0;
        return feed.querySelectorAll('a[href*="/maps/place/"]').length;
      });

      console.log(`📊 Scroll ${i + 1}: Found ${currentCount} places`);

      // If we have enough results
      if (currentCount >= targetCount) {
        console.log(`✅ Reached target: ${currentCount}/${targetCount}`);
        break;
      }

      // Check if no new results are loading (hit the end)
      if (currentCount === previousCount) {
        stableCount++;
        if (stableCount >= 3) {
          console.log(`⚠️  No more results available. Found ${currentCount} total.`);
          break;
        }
      } else {
        stableCount = 0;
      }

      previousCount = currentCount;

      // Check for "You've reached the end" message
      const reachedEnd = await page.evaluate(() => {
        const text = document.body.innerText.toLowerCase();
        return text.includes('reached the end') ||
               text.includes('no more results') ||
               text.includes('hết kết quả');
      });

      if (reachedEnd) {
        console.log(`🏁 Reached end of results at ${currentCount} places`);
        break;
      }
    }
  }

  private async extractPlaceFromUrl(page: Page, url: string): Promise<ScrapedPlace | null> {
    try {
      await page.goto(url, { waitUntil: 'networkidle2', timeout: 20000 });
      await page.waitForTimeout(1000);

      // Extract details
      const details = await page.evaluate(() => {
        const data: any = {};

        // Name
        const nameEl = document.querySelector('h1.fontHeadlineLarge, h1.DUwDvf');
        data.name = nameEl?.textContent?.trim() || '';

        // Phone - multiple selectors
        const phoneSelectors = [
          'button[data-item-id^="phone"]',
          'button[aria-label*="Phone"]',
          'a[href^="tel:"]',
          'button[data-tooltip*="phone"]'
        ];

        for (const selector of phoneSelectors) {
          const phoneEl = document.querySelector(selector);
          if (phoneEl) {
            let phone = phoneEl.getAttribute('data-item-id')?.replace('phone:tel:', '') ||
                       phoneEl.getAttribute('href')?.replace('tel:', '') ||
                       phoneEl.textContent?.trim();
            if (phone && phone.length > 5) {
              data.phone = phone;
              break;
            }
          }
        }

        // Address - multiple methods
        const addressSelectors = [
          'button[data-item-id^="address"]',
          'button[aria-label*="Address"]',
          '[data-tooltip="Copy address"]',
          'div.rogA2c'
        ];

        for (const selector of addressSelectors) {
          const addressEl = document.querySelector(selector);
          if (addressEl) {
            let address = addressEl.getAttribute('aria-label') ||
                         addressEl.textContent?.trim();
            if (address && address.length > 5) {
              // Clean up address
              address = address.replace(/^Address:\s*/i, '');
              data.address = address;
              break;
            }
          }
        }

        return data;
      });

      // Get coordinates from URL
      const currentUrl = page.url();
      const coordsMatch = currentUrl.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
      if (coordsMatch) {
        details.latitude = parseFloat(coordsMatch[1]);
        details.longitude = parseFloat(coordsMatch[2]);
      }

      return details.name ? details : null;
    } catch (error) {
      console.error('Error extracting place details:', error);
      return null;
    }
  }

  // Retry wrapper for reliability
  async scrapeWithRetry(options: ScrapeOptions, maxRetries = 2): Promise<ScrapedPlace[]> {
    let lastError: any;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        console.log(`🔄 Attempt ${attempt}/${maxRetries}`);
        return await this.scrape(options);
      } catch (error) {
        lastError = error;
        console.error(`❌ Attempt ${attempt} failed:`, error);

        if (attempt < maxRetries) {
          console.log('⏳ Retrying in 3 seconds...');
          await new Promise(resolve => setTimeout(resolve, 3000));

          // Restart browser
          await this.close();
          await this.init();
        }
      }
    }

    throw lastError;
  }
}
