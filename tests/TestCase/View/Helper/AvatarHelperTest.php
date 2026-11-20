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
}
