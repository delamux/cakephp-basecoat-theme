<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\AvatarHelper;

class AvatarHelperTest extends HelperTestCase
{
    public function testInitialsFallback(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame('<span class="avatar"><span>ID</span></span>', $avatar->render('Ines van Duarte'));
        $this->assertSame('QS', $avatar->initials('quillon studio'));
    }

    public function testImageSizeAndBadge(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame(
            '<span class="avatar rounded-lg" data-size="lg"><img src="https://cdn.example.com/avatars/kofi.png" alt="Kofi Mensah"><span>KM</span><span class="avatar-badge"></span></span>',
            $avatar->render('Kofi Mensah', [
                'src' => 'https://cdn.example.com/avatars/kofi.png',
                'initials' => 'KM',
                'size' => 'lg',
                'class' => 'rounded-lg',
                'badge' => true,
            ]),
        );
    }

    public function testGroupWithMax(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame(
            '<div class="avatar-group"><span class="avatar" data-size="sm"><span>A</span></span><span class="avatar" data-size="sm"><span>B</span></span><span data-count>+2</span></div>',
            $avatar->group(['A', 'B', 'C', ['name' => 'D']], ['max' => 2, 'avatar' => ['size' => 'sm']]),
        );
    }

    public function testDecorativeHidesInitialsAndImageButKeepsBadgeText(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame(
            '<span class="avatar"><img src="https://cdn.example.com/k.png" alt=""><span aria-hidden="true">KM</span><span class="avatar-badge"><span class="sr-only">Online</span></span></span>',
            $avatar->render('Kofi Mensah', [
                'src' => 'https://cdn.example.com/k.png',
                'decorative' => true,
                'badge' => '<span class="sr-only">Online</span>',
            ]),
        );
    }

    public function testDecorativeInGroupAndDefaultUnchanged(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame(
            '<div class="avatar-group"><span class="avatar"><span aria-hidden="true">A</span></span></div>',
            $avatar->group(['A'], ['avatar' => ['decorative' => true]]),
        );
        $this->assertStringNotContainsString('aria-hidden', $avatar->render('Ines Duarte'));
        $this->assertStringNotContainsString('decorative', $avatar->render('Ines Duarte', ['decorative' => false]));
    }
}
