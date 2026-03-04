<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_user_can_check_its_exact_role(): void
    {
        $teknisi = new User(['role' => 'teknisi']);
        $admin = new User(['role' => 'admin']);
        $atasan = new User(['role' => 'atasan']);
        $owner = new User(['role' => 'owner']);

        $this->assertTrue($teknisi->isTeknisi());
        $this->assertFalse($teknisi->isAdmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isAtasan());

        $this->assertTrue($atasan->isAtasan());
        $this->assertTrue($owner->isOwner());
    }

    public function test_role_permissions_for_input(): void
    {
        $teknisi = new User(['role' => 'teknisi']);
        $admin = new User(['role' => 'admin']);
        $atasan = new User(['role' => 'atasan']);
        $owner = new User(['role' => 'owner']);

        // Only teknisi, admin, owner can input
        $this->assertTrue($teknisi->canInput());
        $this->assertTrue($admin->canInput());
        $this->assertTrue($owner->canInput());
        $this->assertFalse($atasan->canInput());
    }

    public function test_role_permissions_for_manage_status(): void
    {
        $teknisi = new User(['role' => 'teknisi']);
        $admin = new User(['role' => 'admin']);
        $atasan = new User(['role' => 'atasan']);

        // Only admin, atasan, owner can manage status
        $this->assertFalse($teknisi->canManageStatus());
        $this->assertTrue($admin->canManageStatus());
        $this->assertTrue($atasan->canManageStatus());

        // Same for manage users
        $this->assertFalse($teknisi->canManageUsers());
        $this->assertTrue($admin->canManageUsers());
        $this->assertTrue($atasan->canManageUsers());
    }
}
