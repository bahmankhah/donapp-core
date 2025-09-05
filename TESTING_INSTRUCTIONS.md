# Testing Instructions for Gravity Flow Inbox CSV Export

## 🧪 **Ready for Testing!**

The CSV export functionality for Gravity Flow inbox is now ready to be tested. Here's what I've implemented:

### 🔧 **What's Been Added:**

1. **JavaScript-Based Export Button Injection**
   - Automatically detects Gravity Flow inbox tables on any page
   - Adds a CSV export button above the table
   - Works with any `[gravityflow page="inbox"]` shortcode

2. **Dual Approach Implementation**
   - **Primary**: SimpleGravityInboxService (JavaScript injection - most reliable)
   - **Backup**: GravityFlowInboxService (PHP hooks - in case Gravity Flow has the hooks)

3. **CSV Export Functionality**
   - Exports inbox data to UTF-8 CSV file
   - Includes proper Persian headers
   - Works with or without Gravity Flow installed

### 📋 **How to Test:**

#### **Step 1: Create a Test Page**
Create a new WordPress page with this content:
```
[gravityflow page="inbox"]
```

#### **Step 2: Visit the Page While Logged In**
- Go to the page you created
- Make sure you're logged in to WordPress
- Look for a blue "خروجی CSV صندوق ورودی" button above any table

#### **Step 3: Test Export**
- Click the export button
- A CSV file should download automatically
- Open the CSV file to verify it contains inbox data

#### **Step 4: Check Browser Console**
- Press F12 to open developer tools
- Go to the Console tab
- Look for messages like:
  - "Gravity Flow inbox table found and export button added" ✅
  - "No Gravity Flow inbox table found on this page" ❌

### 🔍 **What to Look For:**

#### **✅ Success Indicators:**
- Export button appears above the Gravity Flow inbox table
- Button has blue styling with download icon
- Clicking button downloads a CSV file
- CSV file contains proper Persian headers
- Console shows "table found" message

#### **❌ Troubleshooting:**
- **No button appears**: Check if you're logged in and if the page contains `[gravityflow page="inbox"]`
- **Button appears but no download**: Check WordPress admin for any error messages
- **Empty CSV**: Normal if no real Gravity Flow data exists (will show sample data)

### 🎯 **Test Scenarios:**

1. **With Gravity Flow Installed**: Should show real inbox data
2. **Without Gravity Flow**: Should show sample data with Persian headers
3. **Not Logged In**: Should not show export button
4. **Different Page**: Should not show export button on pages without the shortcode

### 📝 **Expected Results:**

- **Export Button**: Blue button with text "خروجی CSV صندوق ورودی"
- **CSV File**: Named like `gravity-inbox-export-2025-09-06-14-30-15.csv`
- **CSV Content**: Persian headers + data rows (real or sample)
- **File Encoding**: UTF-8 with BOM for Excel compatibility

---

## 🚀 **Ready to Test!**

Please test this on a page with the `[gravityflow page="inbox"]` shortcode and let me know:

1. Does the export button appear?
2. Does clicking it download a CSV file?
3. What do you see in the browser console?
4. Any error messages in WordPress admin?

The system is designed to be very robust and should work even if Gravity Flow isn't installed (using sample data).
