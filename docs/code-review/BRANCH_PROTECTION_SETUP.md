# Branch Protection Rules Setup Guide

This guide explains how to configure branch protection rules in GitHub to enforce code review requirements and maintain code quality.

## Table of Contents
- [Overview](#overview)
- [Recommended Settings](#recommended-settings)
- [Step-by-Step Setup](#step-by-step-setup)
- [Branch Strategy](#branch-strategy)
- [Troubleshooting](#troubleshooting)

## Overview

Branch protection rules prevent direct pushes to important branches and enforce code review workflows. This ensures all code changes go through proper review and testing before being merged.

## Recommended Settings

### Main Branch (Production)

Navigate to: **Settings → Branches → Add branch protection rule**

#### Branch name pattern
```
main
```

#### Protection Rules

**Require a pull request before merging** ✅
- ✅ Require approvals: **2**
- ✅ Dismiss stale pull request approvals when new commits are pushed
- ✅ Require review from Code Owners
- ❌ Restrict who can dismiss pull request reviews (optional, for larger teams)
- ✅ Allow specified actors to bypass required pull requests (for emergency hotfixes)
  - Add: DevOps team, Tech leads

**Require status checks to pass before merging** ✅
- ✅ Require branches to be up to date before merging
- Required status checks:
  - ✅ `PHPUnit Tests`
  - ✅ `Code Quality`
  - ✅ `Frontend Tests`
  - ✅ `Security Review`
  - ✅ `PR Validation`
  - ✅ `Test Coverage Review`

**Require conversation resolution before merging** ✅
- All review comments must be resolved

**Require signed commits** ✅ (Recommended for security)
- Ensures commits are verified

**Require linear history** ✅
- Prevents merge commits, enforces rebase or squash

**Require deployments to succeed before merging** ❌
- Optional: Enable if you have staging deployment

**Lock branch** ❌
- Only enable for archived/deprecated branches

**Do not allow bypassing the above settings** ✅
- Enforces rules for everyone (including admins)
- ⚠️ Consider allowing bypass for emergency hotfixes

**Restrict who can push to matching branches** ✅
- Add: DevOps team (for emergency hotfixes only)
- Everyone else must use pull requests

**Allow force pushes** ❌
- Never allow force pushes to main

**Allow deletions** ❌
- Prevent accidental branch deletion

---

### Develop Branch (Staging)

#### Branch name pattern
```
develop
```

#### Protection Rules

**Require a pull request before merging** ✅
- ✅ Require approvals: **1**
- ✅ Dismiss stale pull request approvals when new commits are pushed
- ❌ Require review from Code Owners (optional)
- ✅ Allow specified actors to bypass required pull requests
  - Add: Tech leads

**Require status checks to pass before merging** ✅
- ✅ Require branches to be up to date before merging
- Required status checks:
  - ✅ `PHPUnit Tests`
  - ✅ `Code Quality`
  - ✅ `Frontend Tests`
  - ✅ `PR Validation`

**Require conversation resolution before merging** ✅

**Require signed commits** ❌ (Optional)

**Require linear history** ✅

**Do not allow bypassing the above settings** ❌
- Allow bypass for faster iteration

**Restrict who can push to matching branches** ❌
- More flexible for development

**Allow force pushes** ❌

**Allow deletions** ❌

---

### Release Branches

#### Branch name pattern
```
release/*
```

#### Protection Rules

**Require a pull request before merging** ✅
- ✅ Require approvals: **2**
- ✅ Dismiss stale pull request approvals when new commits are pushed
- ✅ Require review from Code Owners

**Require status checks to pass before merging** ✅
- All checks required

**Require conversation resolution before merging** ✅

**Require signed commits** ✅

**Allow force pushes** ❌

**Allow deletions** ❌

---

### Hotfix Branches

#### Branch name pattern
```
hotfix/*
```

#### Protection Rules

**Require a pull request before merging** ✅
- ✅ Require approvals: **1** (faster for emergencies)
- ❌ Dismiss stale pull request approvals (faster iteration)

**Require status checks to pass before merging** ✅
- Required status checks:
  - ✅ `PHPUnit Tests`
  - ✅ `Security Review`

**Require conversation resolution before merging** ❌
- Allow faster merging for critical fixes

**Allow specified actors to bypass** ✅
- Add: DevOps team, Tech leads

---

## Step-by-Step Setup

### 1. Access Branch Protection Settings

1. Go to your GitHub repository
2. Click **Settings** (top right)
3. Click **Branches** (left sidebar)
4. Click **Add branch protection rule**

### 2. Configure Main Branch

1. Enter branch name pattern: `main`
2. Enable all recommended settings above
3. Add required status checks
4. Click **Create** or **Save changes**

### 3. Configure Develop Branch

1. Click **Add branch protection rule** again
2. Enter branch name pattern: `develop`
3. Enable recommended settings
4. Click **Create**

### 4. Configure Release and Hotfix Branches

Repeat the process for `release/*` and `hotfix/*` patterns.

### 5. Set Up Required Status Checks

Status checks come from your GitHub Actions workflows. Make sure:
- Workflow names match the status check names
- Workflows run on `pull_request` events
- Jobs have clear, descriptive names

Example workflow job name:
```yaml
jobs:
  phpunit:
    name: PHPUnit Tests  # This becomes the status check name
    runs-on: ubuntu-latest
    # ...
```

### 6. Configure CODEOWNERS

1. Create `.github/CODEOWNERS` file (already created)
2. Define code owners for different paths
3. Enable "Require review from Code Owners" in branch protection

### 7. Test the Setup

1. Create a test branch
2. Make a small change
3. Open a pull request
4. Verify all checks run
5. Verify approval requirements work
6. Merge and verify

## Branch Strategy

### Recommended Git Flow

```
main (production)
  ↑
  └── release/v1.2.0
        ↑
        └── develop (staging)
              ↑
              ├── feature/user-authentication
              ├── feature/payment-integration
              └── bugfix/login-error

hotfix/critical-security-fix → main (emergency only)
```

### Branch Naming Conventions

- **Feature branches**: `feature/short-description`
  - Example: `feature/user-authentication`
  
- **Bug fix branches**: `bugfix/short-description`
  - Example: `bugfix/login-error`
  
- **Hotfix branches**: `hotfix/short-description`
  - Example: `hotfix/security-patch`
  
- **Release branches**: `release/v1.2.0`
  - Example: `release/v1.2.0`

### Workflow

1. **Feature Development**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/my-feature
   # Make changes
   git push origin feature/my-feature
   # Open PR to develop
   ```

2. **Release Preparation**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b release/v1.2.0
   # Final testing and bug fixes
   git push origin release/v1.2.0
   # Open PR to main
   ```

3. **Hotfix**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b hotfix/critical-fix
   # Make fix
   git push origin hotfix/critical-fix
   # Open PR to main
   # After merge, also merge to develop
   ```

## Rulesets (New GitHub Feature)

GitHub now offers "Rulesets" as a more flexible alternative to branch protection rules.

### Advantages of Rulesets
- Apply rules to multiple branches with patterns
- More granular control
- Better bypass permissions
- Tag protection
- Easier to manage

### How to Use Rulesets

1. Go to **Settings → Rules → Rulesets**
2. Click **New ruleset**
3. Choose **Branch ruleset**
4. Configure:
   - **Name**: Production Branch Protection
   - **Enforcement status**: Active
   - **Target branches**: `main`, `release/*`
   - **Rules**: Same as branch protection above

### Migration from Branch Protection

If you're using the new rulesets:
1. Create rulesets first
2. Test thoroughly
3. Delete old branch protection rules
4. Update documentation

## Troubleshooting

### Status Checks Not Appearing

**Problem**: Required status checks don't show up in the dropdown

**Solution**:
1. Ensure workflows have run at least once on a PR
2. Check workflow job names match exactly
3. Verify workflows run on `pull_request` events
4. Wait a few minutes and refresh

### Can't Merge Despite Approvals

**Problem**: PR has approvals but can't merge

**Possible causes**:
1. Status checks not passing
2. Branch not up to date
3. Conversations not resolved
4. Stale approvals (new commits pushed)

**Solution**:
1. Check all status checks are green
2. Update branch with base branch
3. Resolve all conversations
4. Request re-approval if needed

### Emergency Hotfix Blocked

**Problem**: Need to deploy urgent fix but blocked by rules

**Solution**:
1. Use bypass permissions (if configured)
2. Or temporarily disable "Do not allow bypassing"
3. Deploy fix
4. Re-enable protection immediately
5. Document the bypass in incident report

### Too Many Required Approvals

**Problem**: Hard to get 2 approvals quickly

**Solution**:
1. Reduce to 1 approval for develop
2. Keep 2 approvals for main
3. Ensure team is responsive
4. Use CODEOWNERS for automatic assignment

## Security Considerations

### Signed Commits

Enable signed commits for maximum security:

```bash
# Generate GPG key
gpg --full-generate-key

# List keys
gpg --list-secret-keys --keyid-format=long

# Configure Git
git config --global user.signingkey YOUR_KEY_ID
git config --global commit.gpgsign true

# Add to GitHub
gpg --armor --export YOUR_KEY_ID
# Paste in GitHub Settings → SSH and GPG keys
```

### Audit Log

Monitor branch protection changes:
1. Go to **Settings → Audit log**
2. Filter by "protected_branch"
3. Review any changes

### Regular Reviews

- Review branch protection rules quarterly
- Update required status checks as workflows change
- Adjust approval requirements based on team size
- Remove inactive code owners

## Best Practices

1. **Start Strict**: It's easier to relax rules than tighten them
2. **Document Exceptions**: If you bypass rules, document why
3. **Regular Audits**: Review protection rules quarterly
4. **Team Training**: Ensure team understands the rules
5. **Monitor Compliance**: Check that rules are being followed
6. **Update as Needed**: Adjust rules as team/project evolves

## Additional Resources

- [GitHub Branch Protection Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches)
- [GitHub Rulesets Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-rulesets/about-rulesets)
- [CODEOWNERS Documentation](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners)

---

**Questions?** Open an issue or contact the DevOps team.
