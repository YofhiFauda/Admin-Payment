# 🤝 Contributing to WHUSNET Admin Payment

Thank you for your interest in contributing! This document provides guidelines and best practices for contributing to the project.

---

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Testing Requirements](#testing-requirements)
- [Documentation](#documentation)

---

## 📜 Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors.

### Expected Behavior

- ✅ Be respectful and professional
- ✅ Accept constructive criticism gracefully
- ✅ Focus on what's best for the project
- ✅ Show empathy towards other contributors

### Unacceptable Behavior

- ❌ Harassment or discriminatory language
- ❌ Trolling or insulting comments
- ❌ Personal or political attacks
- ❌ Publishing others' private information

---

## 🚀 Getting Started

### Prerequisites

Before contributing, ensure you have:

- ✅ Docker & Docker Compose installed
- ✅ Git configured with your name and email
- ✅ Code editor (VS Code recommended)
- ✅ Basic understanding of Laravel & PHP

### Setup Development Environment

```bash
# 1. Fork the repository on GitHub

# 2. Clone your fork
git clone https://github.com/YOUR_USERNAME/Admin-Payment.git
cd Admin-Payment

# 3. Add upstream remote
git remote add upstream https://github.com/ORIGINAL_OWNER/Admin-Payment.git

# 4. Follow Quick Start guide
# See: docs/getting-started/QUICK_START.md
```

---

## 🔄 Development Workflow

### 1. Create a Feature Branch

```bash
# Update your main branch
git checkout main
git pull upstream main

# Create feature branch
git checkout -b feature/your-feature-name

# Or for bug fixes
git checkout -b fix/bug-description
```

### Branch Naming Convention

| Type | Format | Example |
|------|--------|---------|
| Feature | `feature/description` | `feature/add-export-csv` |
| Bug Fix | `fix/description` | `fix/payment-calculation` |
| Hotfix | `hotfix/description` | `hotfix/security-patch` |
| Documentation | `docs/description` | `docs/update-api-guide` |
| Refactor | `refactor/description` | `refactor/service-layer` |
| Test | `test/description` | `test/add-unit-tests` |

### 2. Make Your Changes

```bash
# Make changes to code
# Test your changes locally
# Commit frequently with clear messages
```

### 3. Keep Your Branch Updated

```bash
# Fetch latest changes from upstream
git fetch upstream

# Rebase your branch
git rebase upstream/main

# Resolve conflicts if any
```

### 4. Push Your Changes

```bash
# Push to your fork
git push origin feature/your-feature-name
```

### 5. Create Pull Request

- Go to GitHub and create a Pull Request
- Fill in the PR template
- Link related issues
- Request review from maintainers

---

## 💻 Coding Standards

### PHP Standards (PSR-12)

We follow **PSR-12** coding standards for PHP:

```php
<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Process transaction approval.
     *
     * @param Transaction $transaction
     * @param int $reviewerId
     * @return bool
     */
    public function approve(Transaction $transaction, int $reviewerId): bool
    {
        return DB::transaction(function () use ($transaction, $reviewerId) {
            $transaction->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            // Additional logic...

            return true;
        });
    }
}
```

**Key Points:**
- ✅ Use type hints for parameters and return types
- ✅ Add PHPDoc blocks for methods
- ✅ Use meaningful variable names
- ✅ Keep methods focused (Single Responsibility)
- ✅ Use early returns to reduce nesting

### Laravel Best Practices

```php
// ✅ GOOD: Use Eloquent relationships
$transaction->branches()->attach($branchId, ['allocation_percent' => 50]);

// ❌ BAD: Manual queries
DB::table('transaction_branches')->insert([...]);

// ✅ GOOD: Use query scopes
Transaction::pending()->forBranch($branchId)->get();

// ❌ BAD: Inline conditions everywhere
Transaction::where('status', 'pending')->where('branch_id', $branchId)->get();

// ✅ GOOD: Use form requests for validation
public function store(StoreTransactionRequest $request)

// ❌ BAD: Validation in controller
$request->validate([...]);
```

### JavaScript Standards

```javascript
// ✅ GOOD: Use const/let, not var
const apiUrl = '/api/transactions';
let isLoading = false;

// ✅ GOOD: Use arrow functions
const fetchData = async () => {
    const response = await axios.get(apiUrl);
    return response.data;
};

// ✅ GOOD: Use template literals
console.log(`Transaction ${id} approved`);

// ❌ BAD: String concatenation
console.log('Transaction ' + id + ' approved');

// ✅ GOOD: Destructuring
const { id, status, amount } = transaction;

// ✅ GOOD: Error handling
try {
    await fetchData();
} catch (error) {
    console.error('Failed to fetch:', error);
    showErrorToast(error.message);
}
```

### Blade Templates

```blade
{{-- ✅ GOOD: Use components --}}
<x-button type="primary" @click="handleSubmit">
    Submit
</x-button>

{{-- ✅ GOOD: Escape output --}}
{{ $transaction->description }}

{{-- ⚠️ CAREFUL: Only use when HTML is safe --}}
{!! $trustedHtml !!}

{{-- ✅ GOOD: Use @auth, @can directives --}}
@can('approve', $transaction)
    <button>Approve</button>
@endcan

{{-- ✅ GOOD: Use @forelse for loops --}}
@forelse($transactions as $transaction)
    <tr>...</tr>
@empty
    <tr><td>No data</td></tr>
@endforelse
```

### CSS/Tailwind

```html
<!-- ✅ GOOD: Use Tailwind utility classes -->
<div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
    <h3 class="text-lg font-semibold text-gray-900">Title</h3>
</div>

<!-- ✅ GOOD: Use responsive classes -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- Content -->
</div>

<!-- ✅ GOOD: Group related utilities -->
<button class="
    px-4 py-2 
    text-white bg-blue-600 
    rounded-lg 
    hover:bg-blue-700 
    focus:outline-none focus:ring-2 focus:ring-blue-500
">
    Click Me
</button>
```

---

## 📝 Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

| Type | Description | Example |
|------|-------------|---------|
| `feat` | New feature | `feat(rembush): add OCR retry mechanism` |
| `fix` | Bug fix | `fix(payment): correct calculation logic` |
| `docs` | Documentation | `docs(api): update endpoint descriptions` |
| `style` | Code style (formatting) | `style(blade): fix indentation` |
| `refactor` | Code refactoring | `refactor(service): extract price calculation` |
| `test` | Add/update tests | `test(transaction): add approval tests` |
| `chore` | Maintenance tasks | `chore(deps): update Laravel to 12.1` |
| `perf` | Performance improvement | `perf(query): optimize branch allocation query` |

### Examples

```bash
# Good commit messages
feat(price-index): implement IQR outlier detection
fix(dashboard): resolve real-time update race condition
docs(readme): add quick start section
refactor(controller): extract approval logic to service
test(pengajuan): add dual-version system tests

# Bad commit messages (avoid these)
fix bug
update code
changes
wip
asdf
```

### Commit Best Practices

- ✅ Write in present tense ("add feature" not "added feature")
- ✅ Keep subject line under 72 characters
- ✅ Capitalize first letter of subject
- ✅ Don't end subject with period
- ✅ Use body to explain "what" and "why", not "how"
- ✅ Reference issues in footer (`Closes #123`)

---

## 🔀 Pull Request Process

### Before Creating PR

- [ ] Code follows project coding standards
- [ ] All tests pass locally
- [ ] New tests added for new features
- [ ] Documentation updated
- [ ] No merge conflicts with main branch
- [ ] Commit messages follow guidelines
- [ ] Self-review completed

### PR Title Format

```
<type>: <description>

Examples:
feat: Add CSV export for price index
fix: Resolve payment verification race condition
docs: Update API documentation for v4.6
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change)
- [ ] New feature (non-breaking change)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issues
Closes #123
Related to #456

## Changes Made
- Added X feature
- Fixed Y bug
- Refactored Z component

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Manual testing completed

## Screenshots (if applicable)
[Add screenshots here]

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Tests pass locally
```

### Review Process

1. **Automated Checks**
   - CI/CD pipeline runs tests
   - Code style checks (Pint)
   - Static analysis (PHPStan)

2. **Code Review**
   - At least 1 approval required
   - Address all review comments
   - Re-request review after changes

3. **Merge**
   - Squash and merge (for feature branches)
   - Rebase and merge (for hotfixes)
   - Delete branch after merge

---

## 🧪 Testing Requirements

### Test Coverage

- ✅ **Unit Tests:** For services, helpers, utilities
- ✅ **Feature Tests:** For controllers, API endpoints
- ✅ **Integration Tests:** For complex workflows

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TransactionTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter testApprovalFlow
```

### Writing Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_approve_transaction_under_one_million()
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $transaction = Transaction::factory()->pending()->create([
            'amount' => 500000,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->patch("/transactions/{$transaction->id}/status", [
                'status' => 'approved',
            ]);

        // Assert
        $response->assertRedirect();
        $this->assertEquals('completed', $transaction->fresh()->status);
    }
}
```

### Test Best Practices

- ✅ Use descriptive test names
- ✅ Follow Arrange-Act-Assert pattern
- ✅ Test one thing per test
- ✅ Use factories for test data
- ✅ Clean up after tests (RefreshDatabase)
- ✅ Mock external services (n8n, Gemini)

---

## 📚 Documentation

### When to Update Documentation

Update documentation when you:
- ✅ Add new features
- ✅ Change existing behavior
- ✅ Add/modify API endpoints
- ✅ Change configuration options
- ✅ Fix bugs that affect usage

### Documentation Standards

```markdown
# Title (H1)

Brief description of the topic.

---

## Section (H2)

Content with examples.

### Subsection (H3)

More detailed content.

#### Sub-subsection (H4)

Even more detail if needed.

## Code Examples

\`\`\`php
// Always include code examples
$example = 'with syntax highlighting';
\`\`\`

## Notes and Warnings

> ⚠️ **Warning:** Important warning message

> 💡 **Tip:** Helpful tip

> 📝 **Note:** Additional information
```

---

## 🎯 Areas for Contribution

### High Priority

- 🔴 Bug fixes
- 🔴 Security improvements
- 🔴 Performance optimizations
- 🔴 Test coverage improvements

### Medium Priority

- 🟡 New features (discuss first)
- 🟡 Refactoring
- 🟡 Documentation improvements
- 🟡 UI/UX enhancements

### Low Priority

- 🟢 Code style improvements
- 🟢 Minor optimizations
- 🟢 Translation updates

---

## ❓ Questions?

- 📖 Check [FAQ.md](../reference/FAQ.md)
- 💬 Ask in project Slack channel
- 📧 Email: dev@whusnet.com
- 🐛 Open an issue for bugs

---

## 🙏 Thank You!

Your contributions make this project better for everyone. We appreciate your time and effort!

---

**Last Updated:** 4 Mei 2026  
**Maintainer:** WHUSNET Development Team
