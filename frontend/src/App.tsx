import React, { useState } from 'react';
import { ScrapeForm } from './components/ScrapeForm';
import { PlacesList } from './components/PlacesList';

function App() {
  const [refreshTrigger, setRefreshTrigger] = useState(0);

  const handleScrapeComplete = () => {
    setRefreshTrigger(prev => prev + 1);
  };

  return (
    <>
      <div className="header">
        <div className="container">
          <h1>🗺️ Google Maps Scraper</h1>
        </div>
      </div>

      <div className="container">
        <ScrapeForm onScrapeComplete={handleScrapeComplete} />
        <PlacesList refreshTrigger={refreshTrigger} />
      </div>
    </>
  );
}

export default App;
