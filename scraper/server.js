require('dotenv').config();
const express = require('express');
const cors = require('cors');
const scrapeCourses = require('./bracu_scraper');

const app = express();
app.use(cors());

app.get('/scrape-preprereg', async (req, res) => {
  try {
    const data = await scrapeCourses();
    res.json({ data });
  } catch (err) {
    console.error("Scraping failed:", err);
    res.status(500).json({ error: "Scraping failed" });
  }
});

const PORT = 3001;
app.listen(PORT, () => console.log(`✅ Server running on http://localhost:${PORT}`));