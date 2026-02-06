# Unused Code Report

## Files/Code Identified for Potential Deletion

### 1. **Unused Controller - SecureUserController**
**Location:** `src/app/Http/Controllers/Admin/SecureUserController.php`
**Status:** ❌ NOT USED - No routes reference this controller
**Reason:** This appears to be a duplicate of `UserController`. The routes use `UserController` instead.
**Methods in this controller:**
- `index()` - references `admin.users.index` (same as UserController)
- `create()` - references `admin.users.create` (same as UserController)
- `store()` - references `admin.users.index` (same as UserController)
- `edit()` - references `admin.users.edit` (same as UserController)
- `update()` - references `admin.users.index` (same as UserController)
- `destroy()` - references `admin.users.index` (same as UserController)
- `dashboard()` - references `admin.users.dashboard` view (DOES NOT EXIST)
- `showPermissionExamples()` - references `admin.permissions.examples` view (DOES NOT EXIST)

**Recommendation:** DELETE entire file

---

### 2. **Commented Out Route**
**Location:** `src/routes/web.php` (lines 25-27)
**Code:**
```php
// Route::get('/', function () {
//     return view('welcome');
// });
```
**Status:** ❌ COMMENTED OUT
**Recommendation:** DELETE if not needed

---

### 3. **Unused Imports in web.php**
**Location:** `src/routes/web.php`
**Lines:**
- Line 3: `use App\Http\Controllers\ProfileController;` - NOT USED
- Line 19: `use App\Http\Controllers\Coach\DashboardController as CoachDashboardController;` - NOT USED

**Status:** ❌ NOT REFERENCED
**Recommendation:** DELETE unused imports

---

### 4. **Missing Views (Controllers Reference But Views Don't Exist)**
**Location:** Controllers reference these views but files don't exist:

#### RoleController references:
- `admin.roles.index` - Controller line 30, but view file MISSING
- `admin.roles.create` - Controller line 39, but view file MISSING  
- `admin.roles.show` - Controller line 81, but view file MISSING
- `admin.roles.edit` - Controller line 90, but view file MISSING

#### PermissionController references:
- `admin.permissions.index` - Controller line 30, but view file MISSING
- `admin.permissions.show` - Controller line 81, but view file MISSING

**Status:** ⚠️ MISSING VIEWS - Controllers will error if routes are accessed
**Recommendation:** Either CREATE these views or REMOVE the controller methods/routes

---

### 5. **Unused Methods in SecureUserController**
**Location:** `src/app/Http/Controllers/Admin/SecureUserController.php`
- `dashboard()` method (line 181) - references non-existent view `admin.users.dashboard`
- `showPermissionExamples()` method (line 207) - references non-existent view `admin.permissions.examples`

**Status:** ❌ METHODS REFERENCE NON-EXISTENT VIEWS
**Recommendation:** DELETE these methods (or entire controller as per #1)

---

## Summary

### High Priority (Safe to Delete):
1. ✅ **SecureUserController.php** - Entire file (224 lines) - Duplicate, not used
2. ✅ **Commented route** in web.php (3 lines)
3. ✅ **Unused imports** in web.php (2 lines)

### Medium Priority (Need Decision):
4. ⚠️ **Missing views** for Roles and Permissions - Need to either create views or remove routes/methods

### Total Lines of Unused Code:
- SecureUserController: ~224 lines
- Commented code: ~3 lines
- Unused imports: ~2 lines
- **Total: ~229 lines**

---

## Confirmation Required

Please confirm which items you want me to delete:
- [x] Delete SecureUserController.php ✅ DELETED
- [x] Delete commented route in web.php ✅ DELETED
- [x] Remove unused imports from web.php ✅ DELETED
- [ ] Create missing views for Roles/Permissions OR remove those routes/methods
