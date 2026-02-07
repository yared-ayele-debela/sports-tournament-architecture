# ⏱️ Automated Match Timer Implementation

## 📋 Overview

This implementation provides **automated match minute tracking** instead of manual updates. The system now automatically calculates and updates match minutes based on elapsed time, with support for pause/resume functionality.

---

## 🎯 Features

### ✅ **Automated Minute Calculation**
- Minutes are calculated automatically based on elapsed time
- Accounts for paused time
- Respects match duration from tournament settings

### ✅ **Pause/Resume Support**
- Track when matches are paused
- Calculate total paused time
- Resume from correct minute

### ✅ **Multiple Update Methods**
1. **Scheduled Task** (Recommended) - Updates every minute via cron
2. **Manual Command** - Run on-demand
3. **JavaScript Timer** - Frontend real-time updates
4. **API Endpoint** - For external integrations

---

## 🗄️ Database Changes

### New Fields Added to `matches` Table:
- `started_at` - When the match timer started
- `paused_at` - When the match was paused (null if running)
- `total_paused_seconds` - Cumulative paused time
- `last_minute_update` - Last time minute was updated

### Migration:
```bash
php artisan migrate
```

---

## 🔧 Implementation Details

### **1. MatchTimerService**

Service class that handles all timer logic:

**Methods:**
- `start(MatchModel $match)` - Start match timer
- `pause(MatchModel $match)` - Pause match timer
- `resume(MatchModel $match)` - Resume match timer
- `end(MatchModel $match)` - End match and calculate final minute
- `updateMinute(MatchModel $match)` - Calculate and update current minute
- `updateAllActiveMatches()` - Update all active matches
- `getElapsedSeconds(MatchModel $match)` - Get elapsed time
- `getRemainingSeconds(MatchModel $match)` - Get remaining time

**How It Works:**
1. When match starts: `started_at` is set to current time
2. Every minute: Calculates `elapsed_seconds - total_paused_seconds`
3. Converts to minutes: `floor(active_seconds / 60)`
4. Caps at match duration from tournament settings

---

### **2. Scheduled Task (Recommended)**

**File:** `routes/console.php`

```php
Schedule::command('matches:update-minutes')->everyMinute();
```

**Setup:**
1. Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

2. Or use Laravel's scheduler:
```bash
php artisan schedule:work  # For development
```

**Benefits:**
- ✅ Automatic updates every minute
- ✅ No manual intervention needed
- ✅ Works for all active matches
- ✅ Server-side, reliable

---

### **3. Manual Command**

**Command:**
```bash
php artisan matches:update-minutes
```

**Use Cases:**
- Testing
- Manual updates
- Debugging
- One-time updates

---

### **4. JavaScript Timer (Frontend)**

For real-time updates on the referee dashboard:

```javascript
// Auto-update minute every 60 seconds
setInterval(function() {
    fetch('/api/v1/matches/' + matchId + '/update-minute', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('current-minute').textContent = data.current_minute + "'";
    });
}, 60000); // Update every 60 seconds
```

**Benefits:**
- ✅ Real-time display
- ✅ No page refresh needed
- ✅ Good for referee dashboard

---

## 📝 Usage

### **Starting a Match**

```php
// In MatchController
$this->timerService->start($match);
```

**What happens:**
- Status → `in_progress`
- `current_minute` → `0`
- `started_at` → `now()`
- `paused_at` → `null`
- `total_paused_seconds` → `0`

---

### **Pausing a Match**

```php
$this->timerService->pause($match);
```

**What happens:**
- Status → `paused`
- Calculates paused time since last update
- Adds to `total_paused_seconds`
- Sets `paused_at` → `now()`

---

### **Resuming a Match**

```php
$this->timerService->resume($match);
```

**What happens:**
- Status → `in_progress`
- Calculates pause duration
- Adds to `total_paused_seconds`
- Sets `paused_at` → `null`
- Updates `last_minute_update`

---

### **Ending a Match**

```php
$this->timerService->end($match);
```

**What happens:**
- Calculates final minute
- Status → `completed`
- Clears `paused_at`

---

## 🔄 Updated Controllers

### **Referee MatchController**

Now uses `MatchTimerService`:
- `start()` - Uses timer service
- `pause()` - Uses timer service
- `resume()` - New method
- `end()` - Uses timer service

**Manual update still available:**
- `updateMinute()` - For manual overrides

---

## 🛣️ Routes

### **Existing Routes:**
- `POST /admin/referee/matches/{match}/start`
- `POST /admin/referee/matches/{match}/pause`
- `POST /admin/referee/matches/{match}/end`
- `POST /admin/referee/matches/{match}/update-minute`

### **New Route:**
- `POST /admin/referee/matches/{match}/resume` (if needed)

---

## ⚙️ Configuration

### **Match Duration**

Set in `tournament_settings`:
- `match_duration` - Duration in minutes (default: 90)

The timer automatically caps at this duration.

---

## 🧪 Testing

### **Test Timer Service:**

```bash
php artisan tinker
```

```php
$match = \App\Models\MatchModel::find(1);
$timer = app(\App\Services\MatchTimerService::class);

// Start match
$timer->start($match);

// Wait 2 minutes...

// Update minute
$timer->updateMinute($match);
$match->fresh()->current_minute; // Should be 2

// Pause
$timer->pause($match);

// Wait 1 minute...

// Resume
$timer->resume($match);

// Update minute (should still be ~2, not 3)
$timer->updateMinute($match);
```

---

## 📊 Monitoring

### **Check Active Matches:**

```php
$activeMatches = \App\Models\MatchModel::where('status', 'in_progress')
    ->whereNotNull('started_at')
    ->get();

foreach ($activeMatches as $match) {
    echo "Match {$match->id}: {$match->current_minute} minutes\n";
}
```

---

## 🚀 Deployment

### **1. Run Migration:**
```bash
php artisan migrate
```

### **2. Setup Cron:**
```bash
crontab -e
```

Add:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### **3. Test Command:**
```bash
php artisan matches:update-minutes
```

---

## 🔍 Troubleshooting

### **Minutes Not Updating?**

1. **Check cron is running:**
   ```bash
   php artisan schedule:list
   ```

2. **Check match status:**
   ```php
   $match->status; // Should be 'in_progress'
   $match->started_at; // Should not be null
   ```

3. **Manually run command:**
   ```bash
   php artisan matches:update-minutes
   ```

### **Minutes Incorrect?**

1. **Check paused time:**
   ```php
   $match->total_paused_seconds;
   ```

2. **Check started time:**
   ```php
   $match->started_at;
   ```

3. **Recalculate:**
   ```php
   $timer = app(\App\Services\MatchTimerService::class);
   $timer->updateMinute($match);
   ```

---

## 📈 Performance

- **Scheduled Task:** Runs every minute, processes all active matches
- **Efficient:** Only updates when minute changes
- **Scalable:** Handles multiple matches simultaneously

---

## 🎯 Benefits

✅ **Automatic** - No manual updates needed  
✅ **Accurate** - Based on actual elapsed time  
✅ **Pause Support** - Handles interruptions  
✅ **Reliable** - Server-side calculation  
✅ **Flexible** - Multiple update methods  

---

## 🔄 Migration from Manual

1. **Existing matches:** Will work with manual updates
2. **New matches:** Use `start()` method to enable auto-timer
3. **Gradual migration:** Can use both methods during transition

---

**Status:** ✅ Complete - Automated Match Timer Implemented
