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
    pipe: true,
    args: launchArgs,
  });
}

// Helper function to convert wkhtmltopdf (which returns a stream) into a Buffer
function generatePDFBuffer(htmlOrUrl, wkOptions = {}) {
  return new Promise((resolve, reject) => {
    let pdfChunks = [];
    let stream;

    // If you want to render a URL:
    //   stream = wkhtmltopdf(htmlOrUrl, wkOptions);
    //
    // If you want to render HTML directly:
    //   stream = wkhtmltopdf(Readable.from(htmlOrUrl), wkOptions);
    //
    // We'll handle both here:

    // If you pass a fully qualified URL, wkhtmltopdf will load that page.
    // If you pass raw HTML (with <html> tags) it will treat it as HTML content.
    // Or you can do a small check if it starts with http/https vs. raw HTML.
    if (/^(http|https):\/\//i.test(htmlOrUrl)) {
      // It's a URL
      stream = wkhtmltopdf(htmlOrUrl, wkOptions);
    } else {
      // It's raw HTML
      // Use the stream approach to pass the HTML to wkhtmltopdf
      const readable = Readable.from(htmlOrUrl);
      stream = wkhtmltopdf(readable, wkOptions);
    }

    stream.on("data", (chunk) => pdfChunks.push(chunk));
    stream.on("end", () => resolve(Buffer.concat(pdfChunks)));
    stream.on("error", (err) => reject(err));
  });
}

// POST endpoint to receive HTML and convert it to PDF
app.post("/html-to-pdf", async (req, res) => {
  const { html, url, options, launch_args, use_wkhtmltopdf } = req.body;

  if (!html && !url) {
    return res.status(400).send("No HTML content or URL provided");
  }

  try {

    // -------------------------------------------------
    // OPTION A: Use wkhtmltopdf if requested
    // -------------------------------------------------
    if (use_wkhtmltopdf) {
      // `options` here can be wkhtmltopdf-specific options (like page size, margins, etc.)
      // e.g., { pageSize: "A4", marginLeft: "10mm", marginRight: "10mm" }
      const pdfBuffer = await generatePDFBuffer(url || html, options || {});
      res.contentType("application/pdf");
      return res.send(pdfBuffer);
    }

    const browser = await launchPuppeteer(launch_args || []);
    const page = await browser.newPage();

    if (url) {
      await page.goto(url, { waitUntil: "networkidle0" });
    } else {
      await page.setContent(html, { timeout: 60000, waitUntil: "domcontentloaded" });
    }

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
  const { html, url, options, launch_args } = req.body;

  if (!html && !url) {
    return res.status(400).send("No HTML content or URL provided");
  }

  try {
    const browser = await launchPuppeteer(launch_args || []);
    const page = await browser.newPage();

    if (url) {
      await page.goto(url, { waitUntil: "networkidle0" });
    } else {
      await page.setContent(html, { timeout: 60000, waitUntil: "domcontentloaded" });
    }
    
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
