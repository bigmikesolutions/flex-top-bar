# Testing Vue.js Admin - API Endpoints

## Quick Test Guide

### 1. Access the Admin Page
Open in browser:
```
http://localhost:8080/wp-admin/options-general.php?page=top-bar
```

**What you should see:**
- Vue app loads
- "Loading..." message briefly
- List of existing bars OR empty state with "Add new Top Bar" button

### 2. Test Basic Functionality

#### Create a Bar
1. Click "Add new Top Bar"
2. A new bar should appear instantly
3. Check browser console - should see successful API call

#### Edit Bar Properties
1. Click to expand a bar
2. Change the name → saves automatically on blur
3. Toggle visibility → saves immediately
4. Change position (top/bottom) → saves immediately
5. Change background color → saves on blur
6. Select effect (none/slider/fadein/blink) → saves immediately

#### Messages
1. Type in message field → saves on blur
2. Click "Add Message" → new field appears
3. Type in new message → saves on blur
4. Click X on second message → removes it

#### Advanced Features
1. Toggle "Show messages on mobile" → saves immediately
2. Set frame width > 0 → color picker appears
3. Change frame color → saves on blur
4. Select "Hide when scrolling" → saves immediately

#### Schedule (if FF_SCHEDULE is true)
1. Check "Scheduled" checkbox
2. Date/time pickers appear
3. Set from/to dates
4. Dates save on blur

#### Delete Bar
1. Click trash icon
2. Confirm dialog appears
3. Bar is deleted immediately

### 3. Test with Browser DevTools

#### Check Network Tab
1. Open DevTools → Network tab
2. Filter: `top-bar`
3. You should see:
   - `GET /wp-json/top-bar/v1/bars` (on page load)
   - `GET /wp-json/top-bar/v1/feature-flags` (on page load)
   - `PUT /wp-json/top-bar/v1/bars/{id}` (on updates)
   - `POST /wp-json/top-bar/v1/bars` (on create)
   - `DELETE /wp-json/top-bar/v1/bars/{id}` (on delete)

#### Check Console
- No errors should appear
- Look for successful API responses
- Vue Devtools available if installed

### 4. Test Feature Flags

#### Test Max Bars Limit
1. Keep adding bars until you reach the limit
2. "Add new Top Bar" button should disappear
3. Warning message should appear if you try

#### Test Max Messages Limit
1. In a bar, keep clicking "Add Message"
2. Should stop at max_messages limit
3. "Add Message" button should disappear

### 5. Test Data Persistence

1. Make several changes to bars
2. Refresh the page
3. All changes should be saved and loaded correctly

### 6. Verify Backend Integration

#### Check Database
```bash
docker compose exec wordpress wp option get top_bars --format=json
```

Should show array of bars with all properties.

#### Check Frontend Rendering
1. Visit your site homepage: `http://localhost:8080`
2. Visible bars should appear at top or bottom
3. Should match settings from admin

### 7. Test Error Handling

#### Network Error Simulation
1. Stop Docker containers
2. Try to make changes in admin
3. Should see error messages
4. Changes should not be lost when containers restart

### 8. Mobile Responsiveness (Optional)

1. Open DevTools → Toggle device toolbar
2. Switch to mobile view
3. Admin interface should still be usable

## Expected Behavior

### ✅ Good Signs
- Instant UI updates on all changes
- No page reloads
- Changes persist after refresh
- Console shows successful API calls (200/201/204)
- Loading states show briefly
- Error messages if API fails

### ❌ Problems to Watch For
- Console errors
- Changes don't save
- UI doesn't update
- Network errors (401, 403, 404, 500)
- White screen
- Old PHP admin shows instead of Vue

## Troubleshooting

### Vue App Not Loading
```bash
# Check if files exist
ls -lh assets/dist/js/admin.js
ls -lh assets/dist/css/admin.css

# Rebuild if missing
npm run build
```

### API Returns 401/403
- Make sure you're logged in as admin
- Check nonce is being passed correctly
- Verify `manage_options` capability

### Changes Don't Save
- Check browser console for errors
- Check Network tab for failed requests
- Verify API endpoint exists: `http://localhost:8080/wp-json/top-bar/v1/bars`

### Old PHP Admin Shows
- Vue app might not be built
- Check: `file_exists(TOP_BAR_PLUGIN_DIR . 'assets/dist/js/admin.js')`
- Run `npm run build`

## Manual API Testing (curl)

### Get All Bars
```bash
curl -X GET "http://localhost:8080/wp-json/top-bar/v1/bars" \
  -H "Cookie: YOUR_WORDPRESS_COOKIES" \
  --cookie-jar cookies.txt
```

### Create Bar
```bash
curl -X POST "http://localhost:8080/wp-json/top-bar/v1/bars" \
  -H "Content-Type: application/json" \
  -H "Cookie: YOUR_WORDPRESS_COOKIES" \
  -b cookies.txt \
  -d '{
    "name": "Test Bar",
    "visible": true,
    "position": "top",
    "messages": ["Hello from API"]
  }'
```

### Update Bar
```bash
curl -X PUT "http://localhost:8080/wp-json/top-bar/v1/bars/bar_abc123" \
  -H "Content-Type: application/json" \
  -H "Cookie: YOUR_WORDPRESS_COOKIES" \
  -b cookies.txt \
  -d '{
    "name": "Updated Name",
    "bg_color": "#ff0000"
  }'
```

### Delete Bar
```bash
curl -X DELETE "http://localhost:8080/wp-json/top-bar/v1/bars/bar_abc123" \
  -H "Cookie: YOUR_WORDPRESS_COOKIES" \
  -b cookies.txt
```

### Get Feature Flags
```bash
curl -X GET "http://localhost:8080/wp-json/top-bar/v1/feature-flags" \
  -H "Cookie: YOUR_WORDPRESS_COOKIES" \
  -b cookies.txt
```

## Success Criteria

✅ All CRUD operations work
✅ UI updates instantly
✅ Changes persist
✅ Feature flags respected
✅ No console errors
✅ API calls succeed (200/201/204)
✅ Error handling works
✅ Frontend rendering matches admin settings

---

**Status**: Ready for Testing
**Build**: Complete
**API**: Implemented
**UI**: All features included
