<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\AvatarHelper;

class AvatarHelperTest extends HelperTestCase
{
    public function testInitialsFallback(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame('<span class="avatar"><span>LA</span></span>', $avatar->render('Luis del Amo'));
        $this->assertSame('SH', $avatar->initials('shadcn hello'));
    }

    public function testImageSizeAndBadge(): void
    {
        $avatar = new AvatarHelper($this->View);
        $this->assertSame(
            '<span class="avatar rounded-lg" data-size="lg"><img src="https://github.com/shadcn.png" alt="shadcn"><span>CN</span><span class="avatar-badge"></span></span>',
            $avatar->render('shadcn', [
                'src' => 'https://github.com/shadcn.png',
                'initials' => 'CN',
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
