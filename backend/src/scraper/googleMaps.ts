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

export class GoogleMapsScraper {
  private browser: Browser | null = null;

  async init(): Promise<void> {
    this.browser = await puppeteer.launch({
      headless: 'new',
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu'
      ]
    });
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

    try {
      // Set viewport and user agent
      await page.setViewport({ width: 1920, height: 1080 });
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

      const searchQuery = this.buildSearchQuery(options);
      const encodedQuery = encodeURIComponent(searchQuery);
      const url = `https://www.google.com/maps/search/${encodedQuery}`;

      console.log(`Searching: ${searchQuery}`);
      console.log(`URL: ${url}`);

      await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

      // Wait for results to load
      await page.waitForSelector('div[role="feed"]', { timeout: 10000 });

      // Scroll to load more results
      await this.scrollResults(page, maxResults);

      // Extract place data
      const places = await page.evaluate(() => {
        const placeElements = document.querySelectorAll('div[role="feed"] > div > div[jsaction]');
        const extractedPlaces: any[] = [];

        placeElements.forEach((element) => {
          try {
            const nameElement = element.querySelector('div.fontHeadlineSmall');
            const name = nameElement?.textContent?.trim() || '';

            if (name) {
              extractedPlaces.push({ name, element: element.outerHTML });
            }
          } catch (err) {
            console.error('Error extracting place:', err);
          }
        });

        return extractedPlaces;
      });

      // Click on each place to get detailed info
      for (let i = 0; i < Math.min(places.length, maxResults); i++) {
        try {
          const placeData = await this.extractPlaceDetails(page, i);
          if (placeData) {
            results.push(placeData);
          }
        } catch (err) {
          console.error(`Error extracting place ${i}:`, err);
        }
      }

      console.log(`Scraped ${results.length} places`);
    } catch (error) {
      console.error('Scraping error:', error);
      throw error;
    } finally {
      await page.close();
    }

    return results;
  }

  private async scrollResults(page: Page, targetCount: number): Promise<void> {
    const scrollAttempts = Math.ceil(targetCount / 10);

    for (let i = 0; i < scrollAttempts; i++) {
      await page.evaluate(() => {
        const feed = document.querySelector('div[role="feed"]');
        if (feed) {
          feed.scrollTop = feed.scrollHeight;
        }
      });

      await page.waitForTimeout(2000);
    }
  }

  private async extractPlaceDetails(page: Page, index: number): Promise<ScrapedPlace | null> {
    try {
      // Click on the place
      const placeElements = await page.$$('div[role="feed"] > div > div[jsaction]');
      if (index >= placeElements.length) {
        return null;
      }

      await placeElements[index].click();
      await page.waitForTimeout(2000);

      // Extract details
      const details = await page.evaluate(() => {
        const data: any = {};

        // Name
        const nameEl = document.querySelector('h1.fontHeadlineLarge');
        data.name = nameEl?.textContent?.trim() || '';

        // Phone
        const phoneButton = Array.from(document.querySelectorAll('button[data-item-id^="phone"]')).find(
          (btn) => btn.getAttribute('data-item-id')?.includes('phone')
        );
        if (phoneButton) {
          const phoneText = phoneButton.getAttribute('data-item-id');
          data.phone = phoneText?.replace('phone:tel:', '') || '';
        }

        // Address
        const addressButton = Array.from(document.querySelectorAll('button[data-item-id^="address"]')).find(
          (btn) => btn.getAttribute('data-item-id')?.includes('address')
        );
        if (addressButton) {
          const addressText = addressButton.textContent?.trim();
          data.address = addressText || '';
        }

        // Try alternative methods for address
        if (!data.address) {
          const addressEl = document.querySelector('[data-tooltip="Copy address"]');
          if (addressEl) {
            data.address = addressEl.textContent?.trim() || '';
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
      console.error(`Error extracting details for place ${index}:`, error);
      return null;
    }
  }
}
