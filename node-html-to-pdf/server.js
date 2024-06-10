const express = require("express");
const puppeteer = require("puppeteer");
const bodyParser = require("body-parser");

const app = express();
const port = 3000;
const host = "0.0.0.0";

// Middleware to parse JSON bodies
app.use(bodyParser.json({ limit: "50mb" }));

function handleDefaultpupteerLunchArguments(args) {
  const defaultArgs = [
    "--no-sandbox",
    "--disable-setuid-sandbox",
    "--disable-dev-shm-usage",
    "--disable-accelerated-2d-canvas",
    "--disable-gpu",
    "--single-process",
    "--no-first-run",
    "--no-zygote",
    "--disable-background-timer-throttling",
    "--disable-backgrounding-occluded-windows",
    "--disable-renderer-backgrounding",
    "--disable-web-security",
    "--mute-audio",
  ];
  return [...defaultArgs, ...args];
}

function launchPuppeteer(launchArgs) {
  launchArgs = handleDefaultpupteerLunchArguments(launchArgs);
  return puppeteer.launch({
    headless: true,
    args: launchArgs,
  });
}

// POST endpoint to receive HTML and convert it to PDF
app.post("/html-to-pdf", async (req, res) => {
  const { html, options, launch_args } = req.body;

  if (!html) {
    return res.status(400).send("No HTML content provided");
  }

  try {
    const browser = await launchPuppeteer(launch_args || []);
    const page = await browser.newPage();

    await page.setContent(html);
    await page.emulateMediaType("print");

    // wait until fonts are loaded
    await page.evaluateHandle("document.fonts.ready");

    const pdfBuffer = await page.pdf(options);

    await browser.close();

    res.contentType("application/pdf");
    res.send(pdfBuffer);
  } catch (error) {
    console.error(error);
    res.status(500).send("Failed to generate PDF");
  }
});

app.post("/html-to-image", async (req, res) => {
  const { html, options, launch_args } = req.body;

  if (!html) {
    return res.status(400).send("No HTML content provided");
  }

  try {
    const browser = await launchPuppeteer(launch_args || []);
    const page = await browser.newPage();

    await page.setContent(html);
    await page.emulateMediaType("print");

    // wait until fonts are loaded
    await page.evaluateHandle("document.fonts.ready");
    const imageBuffer = await page.screenshot(options);

    await browser.close();

    res.contentType("image/png");
    res.send(imageBuffer);
  } catch (error) {
    console.error(error);
    res.status(500).send("Failed to generate image");
  }
});

app.listen(port, host, () => {
  console.log(`Server running at http://${host}:${port}/`);
});
