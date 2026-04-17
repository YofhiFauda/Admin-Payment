
import os
import time
from playwright.sync_api import sync_playwright

BASE_URL = "https://inclusive-phones-classic-branches.trycloudflare.com"

def test_pengajuan_flow():
    with sync_playwright() as p:
        # 1. Login as Teknisi
        browser = p.chromium.launch(headless=True)
        # Add a longer timeout for Cloudflare tunnels
        context = browser.new_context()
        page = context.new_page()
        page.set_default_timeout(60000)
        
        print(f"Opening {BASE_URL}/login...")
        page.goto(f"{BASE_URL}/login")
        print(f"Page Title: {page.title()}")
        try:
            page.wait_for_selector('input[name="email"]', timeout=10000)
        except Exception as e:
            page.screenshot(path="debug_login.png")
            print(f"Content: {page.content()[:500]}")
            raise e
        
        print("Logging in as Teknisi...")
        page.fill('input[name="email"]', "teknisi@whusnet.com")
        page.fill('input[name="password"]', "password")
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")
        
        # 2. Input Pengajuan
        print("Creating Pengajuan...")
        page.goto(f"{BASE_URL}/pengajuan/form")
        page.wait_for_load_state("networkidle")
        
        # Select Branch 1 & 2 (OLT JETIS & OLT SIMAN)
        # Using .branch-pill which has data-id
        page.click('button.branch-pill[data-id="1"]')
        page.click('button.branch-pill[data-id="2"]')
        
        # Fill Item 1
        page.fill('input[name="items[0][customer]"]', "Vendor A")
        page.fill('input[name="items[0][description]"]', "Barang 1")
        # Use select for category
        page.select_option('select[name="items[0][category]"]', label="Peralatan")
        page.fill('input[name="items[0][quantity]"]', "1")
        # For estimated price, the form has a mask or something? Let's check the HTML.
        # resources/views/transactions/form-pengajuan.blade.php uses .nominal-input
        page.fill('input[name="items[0][estimated_price]"]', "600000")
        
        # Add Item 2
        page.click('#add-item-btn')
        page.fill('input[name="items[1][customer]"]', "Vendor B")
        page.fill('input[name="items[1][description]"]', "Barang 2")
        page.select_option('select[name="items[1][category]"]', label="Peralatan")
        page.fill('input[name="items[1][quantity]"]', "1")
        page.fill('input[name="items[1][estimated_price]"]', "600000")
        
        # Submit
        page.click('button[type="submit"]')
        page.wait_for_url(f"{BASE_URL}/transactions")
        print("Pengajuan created successfully.")
        
        # Get Transaction ID from URL or list
        # We can find the first row in the list
        page.wait_for_selector('tr.desktop-row')
        first_row = page.locator('tr.desktop-row').first
        # Find invoice number or something to identify it
        invoice_number = first_row.locator('.text-blue-500').inner_text()
        print(f"Invoice Number: {invoice_number}")
        
        # Check initial status
        status_label = first_row.locator('.status-badge').inner_text()
        print(f"Initial Status Label: {status_label}")
        
        # 3. Edit Pengajuan as Atasan
        print("Logging in as Atasan to edit...")
        context.clear_cookies()
        page.goto(f"{BASE_URL}/login")
        page.fill('input[name="email"]', "atasan@whusnet.com")
        page.fill('input[name="password"]', "password")
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")
        
        # Go to Edit page
        # We need the transaction ID. It's usually in the row's data-id or in the edit link.
        edit_link = page.locator(f'a[href*="/edit"]').first.get_attribute('href')
        transaction_id = edit_link.split('/')[-2]
        print(f"Transaction ID: {transaction_id}")
        
        page.goto(f"{BASE_URL}/transactions/{transaction_id}/edit")
        page.wait_for_load_state("networkidle")
        
        # Edit description
        page.fill('input[name="items[0][description]"]', "Barang 1 - Edited")
        page.click('button[type="submit"]')
        page.wait_for_url(f"{BASE_URL}/transactions")
        print("Pengajuan edited successfully.")
        
        # 4. Approve as Atasan
        print("Approving as Atasan...")
        # Since we are on the list, find the Approve button
        # Usually it's in the detail modal or inline
        # Looking at index.blade.php, inline actions have "Terima" or similar
        # For Pengajuan, Atasan sees "Terima"
        page.locator(f'button[onclick*="performStatusAction(\'{transaction_id}\', \'approved\'"]').click()
        # Wait for Reverb/AJAX to update
        time.sleep(2)
        
        # Verify status label "Menunggu Approve Owner"
        first_row = page.locator('tr.desktop-row').first
        status_label = first_row.locator('.status-badge').inner_text()
        print(f"Status after Atasan approval: {status_label}")
        assert "Approve Owner" in status_label
        
        # 5. Approve as Owner (Superadmin)
        print("Logging in as Owner to approve...")
        context.clear_cookies()
        page.goto(f"{BASE_URL}/login")
        page.fill('input[name="email"]', "superadmin@whusnet.com")
        page.fill('input[name="password"]', "superadmin")
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")
        
        print("Approving as Owner...")
        page.locator(f'button[onclick*="performStatusAction(\'{transaction_id}\', \'waiting_payment\'"]').click()
        time.sleep(2)
        
        # Verify status label "Menunggu Pembayaran"
        first_row = page.locator('tr.desktop-row').first
        status_label = first_row.locator('.status-badge').inner_text()
        print(f"Status after Owner approval: {status_label}")
        assert "Menunggu Pembayaran" in status_label
        
        # 6. Upload Invoice + Ongkir & Trigger Debt
        print("Uploading Invoice and triggering debt...")
        # Open Payment Modal
        page.locator(f'button[onclick*="openPaymentModal(\'{transaction_id}\'"]').click()
        # Wait for modal body to show
        page.wait_for_selector('#payment-body:not(.hidden)')
        
        # Fill Ongkir
        page.fill('#p_ongkir', "50000") # 50k
        
        # Total is 1.2jt + 50k = 1.25jt
        # Allocation is 50/50: JETIS 625k, SIMAN 625k
        # Trigger debt by selecting only OLT JETIS to pay the whole 1.25jt
        
        # Check OLT JETIS (Branch ID 1)
        # Note: id="sd_check_1"
        page.click('#sd_check_1')
        # Fill amount for OLT JETIS
        page.fill('#sd_amount_1', "1250000")
        
        # Verify Debt Preview is visible
        page.wait_for_selector('#p_debt_preview:not(.hidden)')
        print("Debt Preview confirmed.")
        
        # Upload file
        # name="invoice_file"
        page.set_input_files('input[name="invoice_file"]', "test.png")
        
        # Submit Payment
        page.click('#btnSubmitPayment')
        time.sleep(3) # Wait for processing
        
        # 7. Verify Status "Menunggu Pelunasan Hutang"
        first_row = page.locator('tr.desktop-row').first
        status_label = first_row.locator('.status-badge').inner_text()
        print(f"Final Status Label: {status_label}")
        assert "Menunggu Pelunasan Hutang" in status_label
        
        print("All tests passed!")
        browser.close()

if __name__ == "__main__":
    test_pengajuan_flow()
