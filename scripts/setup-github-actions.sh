#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Setup GitHub Actions CI/CD
#  Script untuk membantu setup secrets dan konfigurasi
# ═══════════════════════════════════════════════════════════════════

set -e

echo "🚀 GitHub Actions CI/CD Setup Helper"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ─────────────────────────────────────────
#  Check Prerequisites
# ─────────────────────────────────────────
echo "📋 Checking prerequisites..."

if ! command -v gh &> /dev/null; then
    echo -e "${RED}❌ GitHub CLI (gh) not found${NC}"
    echo "Install from: https://cli.github.com/"
    exit 1
fi

if ! command -v ssh-keygen &> /dev/null; then
    echo -e "${RED}❌ ssh-keygen not found${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Prerequisites OK${NC}"
echo ""

# ─────────────────────────────────────────
#  GitHub CLI Login
# ─────────────────────────────────────────
echo "🔐 Checking GitHub CLI authentication..."

if ! gh auth status &> /dev/null; then
    echo -e "${YELLOW}⚠️  Not logged in to GitHub CLI${NC}"
    echo "Please login:"
    gh auth login
else
    echo -e "${GREEN}✅ Already logged in${NC}"
fi
echo ""

# ─────────────────────────────────────────
#  Get Repository Info
# ─────────────────────────────────────────
echo "📦 Getting repository information..."

REPO=$(gh repo view --json nameWithOwner -q .nameWithOwner)
echo "Repository: $REPO"
echo ""

# ─────────────────────────────────────────
#  Generate SSH Key
# ─────────────────────────────────────────
echo "🔑 SSH Key Setup"
echo "─────────────────────────────────────────"

read -p "Do you want to generate a new SSH key for GitHub Actions? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    SSH_KEY_PATH="$HOME/.ssh/github-actions-$REPO"
    SSH_KEY_PATH=$(echo "$SSH_KEY_PATH" | tr '/' '-')
    
    if [ -f "$SSH_KEY_PATH" ]; then
        echo -e "${YELLOW}⚠️  SSH key already exists: $SSH_KEY_PATH${NC}"
        read -p "Overwrite? (y/n): " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Using existing key..."
        else
            ssh-keygen -t ed25519 -C "github-actions@$REPO" -f "$SSH_KEY_PATH" -N ""
            echo -e "${GREEN}✅ SSH key generated${NC}"
        fi
    else
        ssh-keygen -t ed25519 -C "github-actions@$REPO" -f "$SSH_KEY_PATH" -N ""
        echo -e "${GREEN}✅ SSH key generated${NC}"
    fi
    
    echo ""
    echo "📋 Public Key (add this to server's ~/.ssh/authorized_keys):"
    echo "─────────────────────────────────────────"
    cat "${SSH_KEY_PATH}.pub"
    echo "─────────────────────────────────────────"
    echo ""
    
    read -p "Press Enter after you've added the public key to your server..."
    
    # Set SSH_PRIVATE_KEY secret
    echo "Setting SSH_PRIVATE_KEY secret..."
    gh secret set SSH_PRIVATE_KEY < "$SSH_KEY_PATH"
    echo -e "${GREEN}✅ SSH_PRIVATE_KEY secret set${NC}"
fi
echo ""

# ─────────────────────────────────────────
#  Server Configuration
# ─────────────────────────────────────────
echo "🖥️  Server Configuration"
echo "─────────────────────────────────────────"

read -p "Enter server hostname or IP: " SERVER_HOST
if [ -n "$SERVER_HOST" ]; then
    echo "$SERVER_HOST" | gh secret set SERVER_HOST
    echo -e "${GREEN}✅ SERVER_HOST secret set${NC}"
fi

read -p "Enter server SSH username: " SERVER_USER
if [ -n "$SERVER_USER" ]; then
    echo "$SERVER_USER" | gh secret set SERVER_USER
    echo -e "${GREEN}✅ SERVER_USER secret set${NC}"
fi
echo ""

# ─────────────────────────────────────────
#  Environment File
# ─────────────────────────────────────────
echo "📄 Environment File"
echo "─────────────────────────────────────────"

read -p "Enter path to production .env file (or press Enter to skip): " ENV_FILE_PATH

if [ -n "$ENV_FILE_PATH" ] && [ -f "$ENV_FILE_PATH" ]; then
    gh secret set ENV_FILE < "$ENV_FILE_PATH"
    echo -e "${GREEN}✅ ENV_FILE secret set${NC}"
elif [ -n "$ENV_FILE_PATH" ]; then
    echo -e "${RED}❌ File not found: $ENV_FILE_PATH${NC}"
fi
echo ""

# ─────────────────────────────────────────
#  Slack Webhook (Optional)
# ─────────────────────────────────────────
echo "📢 Slack Notification (Optional)"
echo "─────────────────────────────────────────"

read -p "Enter Slack Webhook URL (or press Enter to skip): " SLACK_WEBHOOK_URL

if [ -n "$SLACK_WEBHOOK_URL" ]; then
    echo "$SLACK_WEBHOOK_URL" | gh secret set SLACK_WEBHOOK_URL
    echo -e "${GREEN}✅ SLACK_WEBHOOK_URL secret set${NC}"
fi
echo ""

# ─────────────────────────────────────────
#  Enable GitHub Container Registry
# ─────────────────────────────────────────
echo "🐳 GitHub Container Registry"
echo "─────────────────────────────────────────"
echo "Make sure GitHub Container Registry is enabled:"
echo "1. Go to: https://github.com/$REPO/settings/actions"
echo "2. Under 'Workflow permissions', select 'Read and write permissions'"
echo "3. Check 'Allow GitHub Actions to create and approve pull requests'"
echo ""
read -p "Press Enter after you've configured the permissions..."
echo ""

# ─────────────────────────────────────────
#  Test SSH Connection
# ─────────────────────────────────────────
if [ -n "$SERVER_HOST" ] && [ -n "$SERVER_USER" ] && [ -f "$SSH_KEY_PATH" ]; then
    echo "🔌 Testing SSH Connection"
    echo "─────────────────────────────────────────"
    
    if ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$SERVER_USER@$SERVER_HOST" "echo 'Connection successful'" 2>/dev/null; then
        echo -e "${GREEN}✅ SSH connection successful${NC}"
    else
        echo -e "${RED}❌ SSH connection failed${NC}"
        echo "Please check:"
        echo "1. Server hostname/IP is correct"
        echo "2. SSH username is correct"
        echo "3. Public key is added to server's ~/.ssh/authorized_keys"
        echo "4. Server firewall allows SSH connections"
    fi
    echo ""
fi

# ─────────────────────────────────────────
#  Summary
# ─────────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════"
echo "✅ Setup Complete!"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📋 Secrets configured:"
gh secret list
echo ""
echo "🚀 Next Steps:"
echo "1. Verify secrets at: https://github.com/$REPO/settings/secrets/actions"
echo "2. Check workflow permissions at: https://github.com/$REPO/settings/actions"
echo "3. Test deployment: gh workflow run 'Deploy to Production (Zero Downtime)'"
echo "4. Monitor deployment: gh run watch"
echo ""
echo "📚 Documentation: GITHUB_ACTIONS_SETUP.md"
echo ""

