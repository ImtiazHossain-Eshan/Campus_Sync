const puppeteer = require('puppeteer');

async function scrapeCourses() {
  const browser = await puppeteer.launch({ headless: 'new' }); // use false to debug
  const page = await browser.newPage();
  await page.goto("https://preprereg.vercel.app/", { waitUntil: "networkidle2" });

  await page.waitForSelector('.dual-listbox__item');

  const courseInfo = [];

  const courseHandles = await page.$$('.dual-listbox__item');

  for (let i = 0; i < courseHandles.length; i++) {
    try {
      const courseElement = courseHandles[i];
      const courseText = await page.evaluate(el => el.textContent.trim(), courseElement);
      
      await courseElement.click();
      await page.waitForSelector('#cname'); // Wait for the modal/info to load

      const info = await page.evaluate(() => ({
        courseName: document.querySelector('#cname')?.textContent || '',
        faculty: document.querySelector('#faculty')?.textContent || '',
        section: document.querySelector('#section')?.textContent || '',
        time: document.querySelector('#time')?.textContent || '',
        exam: document.querySelector('#exam')?.textContent || '',
        totalSeat: document.querySelector('#avs')?.textContent || '',
        seatBooked: document.querySelector('#sb')?.textContent || '',
        remaining: document.querySelector('#sr')?.textContent || '',
      }));

      courseInfo.push({
        courseSection: courseText,
        ...info
      });

    } catch (err) {
      console.log(`❌ Failed to load data for: item ${i + 1}`);
    }
  }

  await browser.close();
  return courseInfo;
}

module.exports = scrapeCourses;