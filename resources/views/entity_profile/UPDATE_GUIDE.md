# Entity Profile Edit Page - Update Guide

## Summary of Changes

### 1. UI Style Updates
- Changed from separate sections with borders to cohesive card-based layout matching `pages/edit.blade.php`
- Updated input styling to use consistent padding, borders, and focus states
- Improved spacing and visual hierarchy
- Added proper form labels and help text styling

### 2. Personnel Search Workflow
- **OLD**: Direct personnel ID input → fetch details → show roles
- **NEW**: Search box (ID or name) → show search results → select personnel → show roles → auto-fill info

### 3. Key Functional Changes
- Search query input with real-time validation
- Personnel search results displayed as selectable cards with photos
- Hidden input field for `head_personnel_id` (populated when personnel selected)
- Role assignments fetched and displayed after personnel selection
- Auto-fill of head information (name, designation, photo) when role selected
- Manual override capability maintained for all fields

### 4. API Integration
- Uses `/personnels?search={query}` endpoint for personnel search
- Uses `/personnels/{id}/roles` endpoint for role assignments  
- Maintains existing personnel details fetch logic

## Implementation Notes

The updated file has been saved as:
`c:\wamp64\www\NSTU\nstu-dashboards\resources\views\dashboard\web_curator\entity_profile\edit_NEW.blade.php`

To apply:
1. Review the new file
2. Test the personnel search functionality
3. When ready, rename:
   - `edit.blade.php` → `edit.blade.php.old`
   - `edit_NEW.blade.php` → `edit.blade.php`

## Testing Checklist
- [ ] Personnel search by ID works
- [ ] Personnel search by name works
- [ ] Search results display correctly with photos
- [ ] Role assignments load after personnel selection
- [ ] Head information auto-fills when role selected
- [ ] Manual edits to auto-filled fields persist
- [ ] Form submission includes all required fields
- [ ] TinyMCE editor initializes correctly
- [ ] Photo preview displays correctly
