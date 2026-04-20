from playwright.sync_api import sync_playwright
import time

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()

    page.on("console", lambda msg: print(f"CONSOLE: {msg.text}"))
    page.on("pageerror", lambda err: print(f"ERROR: {err.message}"))
    
    page.goto("http://localhost:8000/transactions", wait_until="networkidle")
    
    time.sleep(2)
    
    page.screenshot(path="playwright_screenshot.png")
    
    browser.close()
