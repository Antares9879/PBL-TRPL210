# 🧪 Database Session Testing Guide

## Quick Test Checklist

Ikuti langkah-langkah ini untuk memverifikasi database session berfungsi dengan baik.

---

## ✅ Test 1: Basic Session Functionality

### **Step 1: Check Configuration**

```bash
php artisan config:show session.driver
```

**Expected Output:**
```
database
```

### **Step 2: Check Sessions Table**

```bash
# Via Artisan
php artisan db:table sessions

# Or via MySQL
mysql -u root -p (database name)
```

```sql
-- Check table structure
DESCRIBE sessions;

-- Check if table is empty (before login)
SELECT COUNT(*) FROM sessions;
```

**Expected:**
- Table exists with 6 columns
- Count = 0 (or existing sessions)

---

## ✅ Test 2: Login & Session Creation

### **Step 1: Login via Browser**

1. Open browser: `http://127.0.0.1:8000/login`
2. Login dengan credentials:
   - Email: (your test user email)
   - Password: (your test password)
3. Verify redirect ke dashboard

### **Step 2: Check Session in Database**

```sql
-- Check new session created
SELECT * FROM sessions ORDER BY last_activity DESC LIMIT 1;

-- Check session count
SELECT COUNT(*) FROM sessions;

-- Check session for specific user
SELECT 
    id,
    user_id,
    ip_address,
    FROM_UNIXTIME(last_activity) as last_active
FROM sessions 
WHERE user_id = 1;  -- Replace with your user ID
```

**Expected:**
- ✅ New row in sessions table
- ✅ user_id matches logged in user
- ✅ ip_address = 127.0.0.1
- ✅ last_activity = current timestamp

---

## ✅ Test 3: Session Persistence

### **Step 1: Navigate Between Pages**

1. Click menu items (Dashboard, Karyawan, etc.)
2. Refresh page (F5)
3. Open new tab, navigate to dashboard

### **Step 2: Verify Session Persists**

```sql
-- Check session still exists
SELECT 
    id,
    user_id,
    FROM_UNIXTIME(last_activity) as last_active,
    TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(last_activity), NOW()) as seconds_ago
FROM sessions 
WHERE user_id = 1;
```

**Expected:**
- ✅ Session still exists
- ✅ last_activity updates on each request
- ✅ User stays logged in

---

## ✅ Test 4: Multi-Tab Login Detection

### **Step 1: Login as User A**

1. Tab 1: Login as **SuperAdmin**
2. Verify dashboard loads

### **Step 2: Check Session**

```sql
SELECT 
    id,
    user_id,
    FROM_UNIXTIME(last_activity) as last_active
FROM sessions 
ORDER BY last_activity DESC;
```

**Expected:**
- ✅ 1 session for SuperAdmin

### **Step 3: Login as User B**

1. Tab 2: Open new tab, go to `/login`
2. Should see warning: "Anda sudah login sebagai..."
3. Login as **Karyawan**
4. Verify dashboard loads

### **Step 4: Check Sessions**

```sql
SELECT 
    id,
    user_id,
    FROM_UNIXTIME(last_activity) as last_active
FROM sessions 
ORDER BY last_activity DESC;
```

**Expected:**
- ✅ 2 sessions (or 1 if SuperAdmin session replaced)
- ✅ Different user_id for each session

### **Step 5: Verify Tab 1 (Session Monitor)**

1. Go back to Tab 1 (SuperAdmin)
2. Wait max 30 seconds
3. Should see notification: "Sesi Berubah"
4. Page auto-reloads

**Expected:**
- ✅ Notification appears
- ✅ Page reloads
- ✅ Redirects to login

---

## ✅ Test 5: Logout

### **Step 1: Logout**

1. Click logout button
2. Verify redirect to login page

### **Step 2: Check Session Deleted**

```sql
-- Check session removed
SELECT COUNT(*) FROM sessions WHERE user_id = 1;

-- Or check all sessions
SELECT * FROM sessions;
```

**Expected:**
- ✅ Session deleted from database
- ✅ Cannot access dashboard without login

---

## ✅ Test 6: Session Expiration

### **Step 1: Set Short Lifetime (Testing)**

```env
# .env (temporary for testing)
SESSION_LIFETIME=1  # 1 minute
```

```bash
php artisan config:clear
```

### **Step 2: Login and Wait**

1. Login to application
2. Wait 2 minutes (do nothing)
3. Try to access any page

**Expected:**
- ✅ Redirected to login
- ✅ Session expired message (if Session Monitor active)

### **Step 3: Check Database**

```sql
-- Check expired session
SELECT 
    id,
    user_id,
    FROM_UNIXTIME(last_activity) as last_active,
    TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(last_activity), NOW()) as minutes_ago
FROM sessions;
```

**Expected:**
- ✅ Session still in database (not auto-deleted)
- ✅ last_activity > 1 minute ago
- ✅ User cannot access (401 Unauthorized)

### **Step 4: Cleanup Expired Sessions**

```bash
php artisan session:gc
```

```sql
-- Verify cleanup
SELECT COUNT(*) FROM sessions;
```

**Expected:**
- ✅ Expired sessions deleted
- ✅ Only active sessions remain

### **Step 5: Restore Normal Lifetime**

```env
# .env
SESSION_LIFETIME=120  # 2 hours
```

```bash
php artisan config:clear
```

---

## ✅ Test 7: Concurrent Sessions

### **Step 1: Login from Multiple Devices/Browsers**

1. Browser 1 (Chrome): Login as User A
2. Browser 2 (Firefox): Login as User A (same user)
3. Browser 3 (Edge): Login as User A (same user)

### **Step 2: Check Sessions**

```sql
-- Check multiple sessions for same user
SELECT 
    id,
    user_id,
    ip_address,
    LEFT(user_agent, 50) as browser,
    FROM_UNIXTIME(last_activity) as last_active
FROM sessions 
WHERE user_id = 1;
```

**Expected:**
- ✅ Multiple sessions for same user
- ✅ Different session IDs
- ✅ Different user_agent (browser)

### **Step 3: Logout from One Browser**

1. Browser 1: Click logout
2. Verify redirect to login

### **Step 4: Check Other Browsers**

1. Browser 2: Refresh page
2. Browser 3: Refresh page

**Expected:**
- ✅ Browser 2 & 3 still logged in
- ✅ Only Browser 1 session deleted

---

## ✅ Test 8: Session Cleanup (Scheduled Task)

### **Step 1: Create Old Sessions (Manual)**

```sql
-- Insert old session (for testing)
INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity)
VALUES (
    'test-old-session',
    NULL,
    '127.0.0.1',
    'Test Browser',
    'YTowOnt9',  -- Empty serialized array
    UNIX_TIMESTAMP() - 10800  -- 3 hours ago
);

-- Verify inserted
SELECT 
    id,
    FROM_UNIXTIME(last_activity) as last_active,
    TIMESTAMPDIFF(HOUR, FROM_UNIXTIME(last_activity), NOW()) as hours_ago
FROM sessions 
WHERE id = 'test-old-session';
```

### **Step 2: Run Cleanup**

```bash
php artisan session:gc
```

### **Step 3: Verify Cleanup**

```sql
-- Check old session deleted
SELECT * FROM sessions WHERE id = 'test-old-session';
```

**Expected:**
- ✅ Old session deleted
- ✅ Active sessions remain

---

## ✅ Test 9: Performance Test

### **Step 1: Check Query Performance**

```sql
-- Test session lookup (should be fast)
EXPLAIN SELECT * FROM sessions WHERE id = 'some-session-id';

-- Test user lookup (should use index)
EXPLAIN SELECT * FROM sessions WHERE user_id = 1;

-- Test cleanup query (should use index)
EXPLAIN SELECT * FROM sessions 
WHERE last_activity < UNIX_TIMESTAMP() - 7200;
```

**Expected:**
- ✅ Uses PRIMARY KEY for id lookup
- ✅ Uses INDEX for user_id lookup
- ✅ Uses INDEX for last_activity lookup

### **Step 2: Measure Response Time**

```bash
# Use browser DevTools Network tab
# Check response time for any page
```

**Expected:**
- ✅ Page load < 500ms
- ✅ Session overhead < 20ms

---

## ✅ Test 10: Error Handling

### **Test 1: Database Connection Lost**

```bash
# Stop MySQL temporarily
# Try to access application
```

**Expected:**
- ✅ Error page shown
- ✅ No PHP fatal error
- ✅ Graceful degradation

### **Test 2: Sessions Table Dropped**

```sql
-- ⚠️ WARNING: Only for testing!
DROP TABLE sessions;
```

```bash
# Try to login
```

**Expected:**
- ✅ Error shown
- ✅ Can recreate table: `php artisan migrate`

---

## 📊 Monitoring Queries

### **Daily Health Check:**

```sql
-- Session statistics
SELECT 
    COUNT(*) as total_sessions,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(CASE WHEN last_activity > UNIX_TIMESTAMP() - 1800 THEN 1 END) as active_30min,
    ROUND(SUM(LENGTH(payload)) / 1024 / 1024, 2) as size_mb
FROM sessions;
```

### **User Activity:**

```sql
-- Most active users (by session count)
SELECT 
    user_id,
    COUNT(*) as session_count,
    MAX(FROM_UNIXTIME(last_activity)) as last_active
FROM sessions 
WHERE user_id IS NOT NULL
GROUP BY user_id 
ORDER BY session_count DESC 
LIMIT 10;
```

### **Session Age Distribution:**

```sql
-- Session age distribution
SELECT 
    CASE 
        WHEN TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(last_activity), NOW()) < 5 THEN '0-5 min'
        WHEN TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(last_activity), NOW()) < 15 THEN '5-15 min'
        WHEN TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(last_activity), NOW()) < 30 THEN '15-30 min'
        WHEN TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(last_activity), NOW()) < 60 THEN '30-60 min'
        ELSE '60+ min'
    END as age_range,
    COUNT(*) as session_count
FROM sessions 
GROUP BY age_range 
ORDER BY age_range;
```

---

## ✅ Success Criteria

Implementation is successful when:

- ✅ All 10 tests pass
- ✅ Sessions stored in database
- ✅ Login/logout works correctly
- ✅ Session persists across requests
- ✅ Multi-tab detection works
- ✅ Session cleanup works
- ✅ Performance is acceptable (<20ms overhead)
- ✅ No errors in logs

---

## 🐛 Troubleshooting

### **Issue: Session not created**

```bash
# Check config
php artisan config:show session

# Clear cache
php artisan config:clear

# Check database connection
php artisan db:show
```

### **Issue: Session not persisting**

```sql
-- Check if session exists
SELECT * FROM sessions ORDER BY last_activity DESC LIMIT 5;

-- Check session lifetime
SELECT 
    id,
    FROM_UNIXTIME(last_activity) as last_active,
    TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(last_activity), NOW()) as minutes_ago
FROM sessions;
```

### **Issue: Too many old sessions**

```bash
# Manual cleanup
php artisan session:gc

# Or via SQL
DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP() - 7200;
```

---

## 📝 Test Results Template

```
Date: _______________
Tester: _______________

Test Results:
[ ] Test 1: Basic Session Functionality
[ ] Test 2: Login & Session Creation
[ ] Test 3: Session Persistence
[ ] Test 4: Multi-Tab Login Detection
[ ] Test 5: Logout
[ ] Test 6: Session Expiration
[ ] Test 7: Concurrent Sessions
[ ] Test 8: Session Cleanup
[ ] Test 9: Performance Test
[ ] Test 10: Error Handling

Issues Found:
_________________________________
_________________________________
_________________________________

Overall Status: [ ] PASS / [ ] FAIL

Notes:
_________________________________
_________________________________
_________________________________
```

---

**Happy Testing! 🧪**
