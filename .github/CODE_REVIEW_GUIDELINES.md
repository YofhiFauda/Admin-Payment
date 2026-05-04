# Code Review Guidelines

## Table of Contents
- [Overview](#overview)
- [Review Process](#review-process)
- [What to Look For](#what-to-look-for)
- [Review Checklist](#review-checklist)
- [Best Practices](#best-practices)
- [Common Issues](#common-issues)
- [Review Etiquette](#review-etiquette)

## Overview

Code reviews are essential for maintaining code quality, sharing knowledge, and catching bugs early. Every pull request must be reviewed by at least one team member before merging.

### Goals of Code Review
- ✅ Ensure code quality and maintainability
- ✅ Catch bugs and security issues early
- ✅ Share knowledge across the team
- ✅ Maintain consistent coding standards
- ✅ Improve overall system design

## Review Process

### 1. Automated Checks (Required)
Before human review, all automated checks must pass:
- ✅ All tests passing
- ✅ Code style checks (Laravel Pint)
- ✅ Security scans
- ✅ No merge conflicts
- ✅ Build successful

### 2. Self-Review (Author)
Before requesting review:
- [ ] Review your own code first
- [ ] Remove debug statements and commented code
- [ ] Update documentation
- [ ] Add/update tests
- [ ] Verify all checklist items in PR template

### 3. Peer Review (Reviewer)
Reviewers should:
- [ ] Review within 24 hours (or communicate delays)
- [ ] Test the changes locally if needed
- [ ] Provide constructive feedback
- [ ] Approve or request changes
- [ ] Re-review after changes

### 4. Approval Requirements
- **Small PRs (<300 lines)**: 1 approval required
- **Medium PRs (300-600 lines)**: 1 approval required
- **Large PRs (>600 lines)**: 2 approvals required
- **Critical changes**: 2+ approvals required (see CODEOWNERS)

## What to Look For

### 🎯 Functionality
- [ ] Does the code do what it's supposed to do?
- [ ] Are edge cases handled?
- [ ] Is error handling appropriate?
- [ ] Are there any logical errors?

### 🏗️ Design & Architecture
- [ ] Is the code in the right place?
- [ ] Does it follow SOLID principles?
- [ ] Is it consistent with existing patterns?
- [ ] Are abstractions appropriate?
- [ ] Is the code reusable where it should be?

### 📝 Code Quality
- [ ] Is the code readable and self-documenting?
- [ ] Are variable/function names clear and descriptive?
- [ ] Is the code DRY (Don't Repeat Yourself)?
- [ ] Is complexity minimized?
- [ ] Are comments helpful (not redundant)?

### 🔒 Security
- [ ] No sensitive data exposed
- [ ] Input validation present
- [ ] SQL injection prevention (use parameter binding)
- [ ] XSS prevention (proper escaping)
- [ ] CSRF protection maintained
- [ ] Authorization checks in place
- [ ] No hardcoded credentials

### ⚡ Performance
- [ ] No N+1 query problems
- [ ] Appropriate use of eager loading
- [ ] Database queries optimized
- [ ] Indexes added where needed
- [ ] Caching considered
- [ ] No unnecessary loops or operations

### 🗄️ Database
- [ ] Migrations are reversible
- [ ] Foreign keys have indexes
- [ ] Column types appropriate
- [ ] No data loss risk
- [ ] Tested on production-like data

### ✅ Testing
- [ ] Tests cover new functionality
- [ ] Tests cover edge cases
- [ ] Tests are meaningful (not just for coverage)
- [ ] Tests are maintainable
- [ ] No flaky tests

### 📚 Documentation
- [ ] Code comments for complex logic
- [ ] README updated if needed
- [ ] API documentation updated
- [ ] CHANGELOG updated
- [ ] Environment variables documented

## Review Checklist

### Quick Review Checklist
Use this for fast reviews:

```markdown
- [ ] Code works as intended
- [ ] Tests added/updated and passing
- [ ] No security issues
- [ ] No performance concerns
- [ ] Code is readable
- [ ] Documentation updated
- [ ] No breaking changes (or properly documented)
```

### Detailed Review Checklist
Use this for thorough reviews:

#### Functionality
- [ ] Requirements met
- [ ] Edge cases handled
- [ ] Error handling appropriate
- [ ] No regressions introduced

#### Code Quality
- [ ] Follows Laravel conventions
- [ ] Follows PSR-12 coding standards
- [ ] No code duplication
- [ ] Appropriate abstractions
- [ ] Clear naming conventions
- [ ] Minimal complexity

#### Security
- [ ] Input validation
- [ ] Output escaping
- [ ] Authorization checks
- [ ] No SQL injection risks
- [ ] No XSS vulnerabilities
- [ ] Secrets not exposed

#### Performance
- [ ] No N+1 queries
- [ ] Proper eager loading
- [ ] Indexes on foreign keys
- [ ] Efficient algorithms
- [ ] Caching where appropriate

#### Testing
- [ ] Unit tests for business logic
- [ ] Feature tests for user flows
- [ ] Edge cases tested
- [ ] Test coverage adequate (>80%)
- [ ] Tests are maintainable

#### Database
- [ ] Migration up/down methods
- [ ] Proper column types
- [ ] Indexes added
- [ ] Foreign key constraints
- [ ] No breaking schema changes

#### Documentation
- [ ] Complex logic commented
- [ ] Public APIs documented
- [ ] README updated
- [ ] CHANGELOG updated
- [ ] Breaking changes noted

## Best Practices

### For Authors

#### Before Requesting Review
1. **Self-review first** - Review your own code as if you were the reviewer
2. **Keep PRs small** - Aim for <300 lines of changes
3. **Write clear descriptions** - Explain what, why, and how
4. **Add context** - Link related issues, provide screenshots
5. **Update tests** - Don't make reviewers ask for tests

#### During Review
1. **Respond promptly** - Address feedback within 24 hours
2. **Be open to feedback** - Don't take it personally
3. **Ask questions** - If feedback is unclear, ask for clarification
4. **Explain decisions** - If you disagree, explain your reasoning
5. **Mark resolved** - Mark conversations as resolved when addressed

### For Reviewers

#### Providing Feedback
1. **Be timely** - Review within 24 hours
2. **Be constructive** - Focus on the code, not the person
3. **Be specific** - Point to exact lines and suggest improvements
4. **Explain why** - Don't just say "change this", explain the reasoning
5. **Praise good work** - Acknowledge clever solutions and improvements

#### Review Techniques
1. **Start with the big picture** - Architecture and design first
2. **Then dive into details** - Line-by-line review
3. **Test locally** - For complex changes, pull and test
4. **Use suggestions** - GitHub's suggestion feature for small fixes
5. **Batch comments** - Submit all comments at once, not one-by-one

#### Types of Comments
Use prefixes to indicate comment severity:

- **🔴 BLOCKER:** Must be fixed before merge
  ```
  🔴 BLOCKER: This SQL query is vulnerable to injection
  ```

- **🟡 IMPORTANT:** Should be fixed before merge
  ```
  🟡 IMPORTANT: This will cause N+1 queries on large datasets
  ```

- **🟢 SUGGESTION:** Nice to have, but not required
  ```
  🟢 SUGGESTION: Consider extracting this to a service class
  ```

- **💡 QUESTION:** Asking for clarification
  ```
  💡 QUESTION: Why did we choose this approach over X?
  ```

- **👍 PRAISE:** Acknowledging good work
  ```
  👍 PRAISE: Great use of the repository pattern here!
  ```

## Common Issues

### Laravel-Specific Issues

#### N+1 Queries
❌ **Bad:**
```php
foreach ($users as $user) {
    echo $user->posts->count();
}
```

✅ **Good:**
```php
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count;
}
```

#### SQL Injection
❌ **Bad:**
```php
DB::select("SELECT * FROM users WHERE email = '$email'");
```

✅ **Good:**
```php
DB::select("SELECT * FROM users WHERE email = ?", [$email]);
```

#### Mass Assignment
❌ **Bad:**
```php
User::create($request->all());
```

✅ **Good:**
```php
User::create($request->validated());
```

#### Missing Authorization
❌ **Bad:**
```php
public function update(Request $request, Post $post)
{
    $post->update($request->validated());
}
```

✅ **Good:**
```php
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);
    $post->update($request->validated());
}
```

### General Issues

#### Magic Numbers
❌ **Bad:**
```php
if ($user->age > 18) {
    // ...
}
```

✅ **Good:**
```php
const MINIMUM_AGE = 18;

if ($user->age > self::MINIMUM_AGE) {
    // ...
}
```

#### Deep Nesting
❌ **Bad:**
```php
if ($user) {
    if ($user->isActive()) {
        if ($user->hasPermission('edit')) {
            // ...
        }
    }
}
```

✅ **Good:**
```php
if (!$user || !$user->isActive() || !$user->hasPermission('edit')) {
    return;
}

// ...
```

## Review Etiquette

### Do's ✅
- ✅ Be respectful and professional
- ✅ Focus on the code, not the person
- ✅ Provide constructive feedback
- ✅ Explain your reasoning
- ✅ Acknowledge good work
- ✅ Ask questions to understand
- ✅ Suggest alternatives
- ✅ Be timely with reviews

### Don'ts ❌
- ❌ Don't be condescending or rude
- ❌ Don't nitpick style (let automated tools handle it)
- ❌ Don't approve without actually reviewing
- ❌ Don't request changes without explanation
- ❌ Don't let PRs sit for days
- ❌ Don't take feedback personally
- ❌ Don't argue in comments (discuss in person/call)

### Example Comments

#### ❌ Bad Comment
```
This is wrong. Change it.
```

#### ✅ Good Comment
```
🟡 IMPORTANT: This approach could lead to race conditions when multiple 
users update the same record simultaneously. Consider using database 
transactions or optimistic locking.

Example:
DB::transaction(function () use ($model, $data) {
    $model->update($data);
});
```

#### ❌ Bad Comment
```
Why did you do it this way?
```

#### ✅ Good Comment
```
💡 QUESTION: I see you're using a raw query here. Is there a specific 
reason we can't use the Eloquent query builder? Raw queries can be 
harder to maintain and test.
```

## Review Response Times

### Expected Response Times
- **Initial review**: Within 24 hours
- **Re-review after changes**: Within 4 hours
- **Urgent/hotfix PRs**: Within 2 hours

### If You Can't Review on Time
- Comment on the PR with expected review time
- Suggest an alternative reviewer
- Remove yourself as reviewer if unavailable

## Approval Guidelines

### When to Approve ✅
- All automated checks pass
- Code meets quality standards
- No security or performance concerns
- Tests are adequate
- Documentation is updated
- All blocking comments addressed

### When to Request Changes 🔄
- Security vulnerabilities present
- Performance issues identified
- Tests missing or inadequate
- Breaking changes not documented
- Code doesn't meet standards

### When to Comment Only 💬
- Minor suggestions that don't block merge
- Questions for clarification
- Praise for good work
- Learning opportunities

## Tools and Resources

### GitHub Features
- **Suggestions**: Propose specific code changes
- **Review comments**: Comment on specific lines
- **Conversations**: Track discussion threads
- **Draft PRs**: Work in progress, not ready for review

### Useful Commands
```bash
# Checkout PR locally
gh pr checkout <PR-number>

# Run tests
php artisan test

# Check code style
./vendor/bin/pint --test

# Run security scan
composer audit
```

### Additional Resources
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [PHP The Right Way](https://phptherightway.com/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Clean Code](https://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882)

## Questions?

If you have questions about the code review process:
1. Check this guide first
2. Ask in the team chat
3. Discuss in team meetings
4. Update this guide with learnings

---

**Remember**: Code reviews are about improving code quality and sharing knowledge, not about being right or wrong. Be kind, be constructive, and help each other grow! 🚀
